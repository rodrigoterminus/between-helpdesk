<?php

namespace AppBundle\Utils;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Notifier
{
    /**
     * @var string
     */
    private $event;

    /**
     * @var \AppBundle\Entity\Ticket
     */
    private $ticket;

    /**
     * @var array
     */
    private $notification;

    /**
     * @var \AppBundle\Entity\User
     */
    private $currentUser;

    /**
     * @var integer
     */
    private $interval = 30;

    /**
     * @var Mailer
     */
    private $mailer;

    /**
     *
     * @param ContainerInterface $container
     * @param EntityManagerInterface $em
     */
    public function __construct(ContainerInterface $container, EntityManagerInterface $em, Mailer $mailer) {
        $this->container = $container;
        $this->em = $em;
        $this->mailer = $mailer;
        $this->router = $this->container->get('router');

        if ($this->container->get('security.token_storage')->getToken() !== null) {
            $this->currentUser = $this->container->get('security.token_storage')->getToken()->getUser();
        }
    }

    /**
     *
     * @return boolean
     * @throws \Exception
     */
    public function notify()
    {
        if ($this->ticket !== null) {
            $users = clone $this->ticket->getWatchers();
            $notification = [
                'event' => 'ticket.'. $this->event,
                'ticket' => $this->ticket->getId(),
            ];

            // Ticket actions
            switch ($this->event) {
                case 'comment':
                    foreach ($users as $user) {
                        if ($user->isAdmin() === false) {
                            $users->removeElement($user);
                        }
                    }

                    if ($users->contains($this->ticket->getAttendant()) === false) {
                        $users->add($this->ticket->getAttendant());
                    }

                    $notification['comment'] = $this->ticket->getComments()->last()->getId();
                    break;
                case 'take':
                case 'transfer':
                    if ($users->contains($this->ticket->getAttendant()) === false) {
                        $users->add($this->ticket->getAttendant());
                    }

                    $notification['entry'] = $this->ticket->getEntries()->last()->getId();
                    break;

                case 'post':
                case 'finish':
                case 'reopen':
                    if ($users->contains($this->ticket->getAttendant()) === false) {
                        $users->add($this->ticket->getAttendant());
                    }

                    // Add ticket's creator wether it is a customer
                    if ($this->ticket->getCreatedBy()->isAdmin() === false && $users->contains($this->ticket->getCreatedBy()) === false) {
                        $users->add($this->ticket->getCreatedBy());
                    }

                    $notification['entry'] = $this->ticket->getEntries()->last()->getId();
                    break;

                default:
                    throw new \Exception('You must to set an event to be notified.');
            }
        } else {
            throw new \Exception('You must to set one of these: Ticket [...].');
        }

        // Remove current user
        if ($users->contains($this->currentUser)) {
            $users->removeElement($this->currentUser);
        }

        // Notify users
        foreach ($users as $user) {
            if ($user !== null) {
                $this->add($user, $notification);
            } else {
                $users->removeElement($user);
            }
        }

        $this->em->flush();

        // Send emails
        $this->mailer
            ->setEvent($this->event)
            ->setTicket($this->ticket)
            ->setUsers($users)
            ->send();

        return true;
    }

    /**
     *
     * @param \AppBundle\Entity\User $user
     * @param array $notification
     * @throws \Exception
     */
    public function add(\AppBundle\Entity\User $user, array $notification)
    {
        $keys = ['event'];

        foreach ($keys as $key => $value) {
            if (array_key_exists($key, $keys) === false) {
                throw new \Exception('Key "'. $key .'" must to be set.');
            }
        }

        $notification['user'] = $this->currentUser->getId();
        $notification['seen'] = (isset($notification['seen'])) ? (boolean) $notification['seen'] : false;
        $notification['origin'] = ($this->currentUser->isAdmin()) ? 'admin' : 'customer';
        $notification['timestamp'] = time();

        $this->notification = $notification;

        $notifications = $user->getNotificationsArray();
        array_unshift($notifications, $notification);

        $user->setNotifications(json_encode($notifications));
        $this->em->persist($user);
    }

    /**
     * Get user notifications
     *
     * @return array
     */
    public function get()
    {
        if ($this->currentUser === null) {
            $this->currentUser = $this->container->get('security.token_storage')->getToken()->getUser();
        }

        $readNotifications = (isset($_COOKIE['notifications_read']))
            ? json_decode($_COOKIE['notifications_read'])
            : [];
        $notificationsRaw = $this->currentUser->getNotificationsArray();
        $notifications = $notificationsRaw;

        foreach ($notifications as $index => $notification) {
            $event = explode('.', $notification['event']);

            if ($event[0] === 'ticket') {
                $ticket = $this->em->getRepository('AppBundle:Ticket')->find($notification['ticket']);

                $notifications[$index]['registred'] = true;
                $notifications[$index]['url'] = $this->router->generate('ticket_edit', ['number' => $ticket->getNumber()], true);
                $notifications[$index]['ticket'] = [
                    'id' => $ticket->getId(),
                    'number' => $ticket->getNumber(),
                ];
                $notifications[$index]['customer'] = [
                    'id' => $ticket->getCustomer()->getId(),
                    'name' => $ticket->getCustomer()->getName(),
                ];

                if (isset($notification['entry'])) {
                    $criteria = Criteria::create()
                        ->where(Criteria::expr()->eq('id', $notification['entry']));

                    $entry = $ticket->getEntries()->matching($criteria)->first();
                }

                if (isset($notification['comment'])) {
                    $criteria = Criteria::create()
                        ->where(Criteria::expr()->eq('id', $notification['comment']));

                    $comment = $ticket->getComments()->matching($criteria)->first();
                }

                switch ($event[1]) {
                    case 'comment':
                        $notifications[$index]['title'] = 'Novo comentário';
                        $notifications[$index]['comment'] = $comment->getText();
                        $notifications[$index]['message'] = [
                            'raw' =>  '{0} comentou no chamado #{1}, "{2}"',
                            'params' => [$comment->getCreatedBy()->getName(), $ticket->getNumber(), $ticket->getSubject()]
                        ];
                        break;

                    case 'finish':
                        $notifications[$index]['title'] = 'Chamado finalizado';
                        $notifications[$index]['message'] = [
                            'raw' =>  '{0} finalizou o chamado #{1}, "{2}"',
                            'params' => [$entry->getCreatedBy()->getName(), $ticket->getNumber(), $ticket->getSubject()]
                        ];
                        break;

                    case 'post':
                        $notifications[$index]['title'] = 'Nova postagem';
                        $notifications[$index]['message'] = [
                            'raw' =>  '{0} postou no chamado #{1}, "{2}"',
                            'params' => [$entry->getCreatedBy()->getName(), $ticket->getNumber(), $ticket->getSubject()]
                        ];
                        break;

                    case 'reopen':
                        $notifications[$index]['title'] = 'Reabertura de chamado';
                        $notifications[$index]['message'] = [
                            'raw' =>  '{0} reabriu o chamado #{1}, "{2}"',
                            'params' => [$entry->getCreatedBy()->getName(), $ticket->getNumber(), $ticket->getSubject()]
                        ];
                        break;

                    case 'take':
                        $notifications[$index]['title'] = 'Chamado assumido';
                        $notifications[$index]['message'] = [
                            'raw' =>  '{0} assumiu o chamado #{1}, "{2}"',
                            'params' => [$entry->getCreatedBy()->getName(), $ticket->getNumber(), $ticket->getSubject()]
                        ];
                        break;

                    case 'transfer':
                        $notifications[$index]['title'] = 'Transferência de chamado';
                        $notifications[$index]['message'] = [
                            'raw' =>  '{0} transferiu o chamado #{1}, "{2}" para {3}',
                            'params' => [$entry->getCreatedBy()->getName(), $ticket->getNumber(), $ticket->getSubject()]
                        ];

                        if ($this->currentUser === $entry->getDirectedTo()) {
                            array_push($notifications[$index]['message']['params'], 'você');
                        } else {
                            array_push($notifications[$index]['message']['params'], $entry->getDirectedTo()->getName());
                        }
                        break;
                }
            }

            if ($notification['seen'] === false && in_array($notification['timestamp'], $readNotifications)) {
                $notifications[$index]['seen'] = true;
                $notificationsRaw[$index]['seen'] = true;
            }
        }

        // Update user's notification
        $this->currentUser->setNotifications(json_encode(array_slice($notificationsRaw, 0, 20)));
        $this->em->persist($this->currentUser);
        $this->em->flush();
        
        $return = [];
        $return['unregistred'] = [];
        $return['registred'] = array_slice($notifications, 0, 20);

        // Get new tickets
        if ($this->currentUser->isAdmin()) {
            $query = $this->em->getRepository('AppBundle:Ticket')->createQueryBuilder('ticket')
                ->select([
                    'ticket.id as ticketId',
                    'ticket.number as ticketNumber',
                    'ticket.subject',
                    'customer.id as customerId',
                    'customer.name as customerName',
                    'attendant.name as attendantName',
                    'ticket.createdAt',
                    'ticket.status',
                ])
                ->join('AppBundle:Customer', 'customer', 'WITH', 'customer.id = ticket.customer')
                ->leftJoin('AppBundle:User', 'attendant', 'WITH', 'attendant.id = ticket.attendant')
                ->where("ticket.status = 'created'")
                ->andWhere('ticket.createdBy <> :user')
                ->andWhere('ticket.createdAt >= :datetime')
                ->setParameter('datetime', new \DateTime('-'. $this->interval .' seconds'))
                ->setParameter('user', $this->currentUser->getId())
                ;

            $result = $query->getQuery()->getResult();

            foreach ($result as $ticket) {
                $return['unregistred'][] = [
                    'customer' => [
                        'id' => $ticket['customerId'],
                        'name' => $ticket['customerName'],
                    ],
                    'event' => 'ticket.new',
                    'message' => [
                        'raw' =>  '#{0}: {1}',
                        'params' => [$ticket['ticketNumber'], $ticket['subject']]
                    ],
                    'seen' => false,
                    'ticket' => [
                        'id' => $ticket['ticketId'],
                        'number' => $ticket['ticketNumber'],
                    ],
                    'timestamp' => time(),
                    'registred' => false,
                    'title' => 'Novo chamado de '. $ticket['customerName'],
                    'url' => $this->router->generate('ticket_edit', ['number' => $ticket['ticketNumber']], true),
                ];
            }
        }

        unset($_COOKIE['notifications_read']);

        return $return;
    }

    /**
     * Set action
     *
     * @param string $event
     * @return \AppBundle\Utils\Notificator
     */
    public function setEvent($event)
    {
        $this->event = $event;

        return $this;
    }

    /**
     * Set Ticket entity
     *
     * @param \AppBundle\Entity\Ticket $ticket
     * @return \AppBundle\Utils\Notificator
     */
    public function setTicket(\AppBundle\Entity\Ticket $ticket)
    {
        $this->ticket = $ticket;

        return $this;
    }

    /**
     *
     * @return type
     */
    function getInterval() {
        return $this->interval;
    }

    /**
     *
     * @param type $interval
     * @return \AppBundle\Utils\Notifier
     */
    function setInterval($interval) {
        $this->interval = $interval;

        return $this;
    }
}
