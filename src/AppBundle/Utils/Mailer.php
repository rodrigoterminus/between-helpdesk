<?php

namespace AppBundle\Utils;

class Mailer
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
     * The logged User
     * 
     * @var \AppBundle\Entity\User 
     */
    private $user;
    
    public function __construct($container) {
        $this->container = $container;
        $this->mailer = $this->container->get('mailer');
        $this->router = $this->container->get('router');
        $this->user = $this->container->get('security.token_storage')->getToken()->getUser();
    }
    
    /**
     * Set action
     * 
     * @param string $event
     * @return \AppBundle\Utils\Mailer
     */
    public function setEvent($event)
    {
        $this->event = $event;
        
        return $this;
    }
    
    /**
     * Set Ticket
     * 
     * @param \AppBundle\Entity\Ticket $ticket
     * @return \AppBundle\Utils\Mailer
     */
    public function setTicket($ticket)
    {
        $this->ticket = $ticket;
        
        return $this;
    }
    
    /**
     * Send emails
     * 
     * @return boolean
     */
    public function send() {
        if ($this->ticket === null) {
            throw new Exception('Ticket not defined.');
        } elseif ($this->event === null) {
            throw new Exception('You must defined which action should be notified.');
        } else {
            $messages = [];
            
            switch ($this->event) {
                case 'transfer':
                    $title = 'Transferência de chamado';
                    $subject = 'Um chamado foi transferido para você';

                    // Avoid the user to receive the email when it transfer the ticket to itself                
                    if ($this->ticket->getAttendant() !== $this->user) {
                        $attendantPreference = $this->ticket->getAttendant()->getPreference('notifications.email.transfer', true);

                        if ($attendantPreference === true || $attendantPreference === null) {
                            $messages[] = [
                                'to' => $this->ticket->getAttendant(),
                                'title' => $title,
                                'subject' => $subject,
                                'content' => 'Olá, ' . $this->ticket->getAttendant()->getName() . '.<br><br>' .
                                'O usuário <b>' . $this->user->getName() . '</b> transferiu o chamado <b>#' . $this->ticket->getNumber() . '</b> para você. ' .
                                'Para acessá-lo, <a href="' . $this->router->generate('ticket_edit', ['number' => $this->ticket->getNumber()], true) . '">clique aqui</a>.',
                            ];
                        }
                    }

                    // Watchers
                    foreach ($this->ticket->getWatchers() as $watcher) {
                        // Watcher notification preference
                        $watcherPreference = $watcher->getPreference('notifications.email.watcher', true);

                        // Avoid current user and the receiver set above to receive the email
                        if ($watcher !== $this->user && $watcher !== $this->ticket->getAttendant() && ($watcherPreference === true || $watcherPreference === null)) {
                            $messages[] = [
                                'to' => $watcher,
                                'title' => 'Chamado #' . $this->ticket->getNumber() . ' transferido',
                                'subject' => 'Chamado #' . $this->ticket->getNumber() . ' transferido',
                                'content' => 'Olá, ' . $watcher->getName() . '.<br><br>' .
                                'O usuário <b>' . $this->user->getName() . '</b> transferiu o chamado <b>#' . $this->ticket->getNumber() . '</b> para o usuário <b>' . $this->ticket->getAttendant()->getName() . '</b>. ' .
                                'Para acessá-lo, <a href="' . $this->router->generate('ticket_edit', ['number' => $this->ticket->getNumber()], true) . '">clique aqui</a>.',
                            ];
                        }
                    }
                    break;

                case 'comment':
                    $users = [];
                    $title = 'Novo comentário';
                    $subject = 'Novo comentário no chamado #'. $this->ticket->getNumber();
                    $content = 'Olá, %s.<br><br>' .
                        'O usuário <b>' . $this->user->getName() . '</b> inseriu um novo comentário no chamado <b>#' . $this->ticket->getNumber() . '</b>. ' .
                        'Para acessá-lo, <a href="' . $this->router->generate('ticket_edit', ['number' => $this->ticket->getNumber()], true) . '">clique aqui</a>.';

                    // Add attendant                
                    if ($this->ticket->getAttendant() !== $this->user) {
                        $attendantPreference = $this->ticket->getAttendant()->getPreference('notifications.email.comment', true);

                        if ($attendantPreference === true || $attendantPreference === null) {
                            $users[] = $this->ticket->getAttendant();
                        }
                    }

                    // Watchers
                    foreach ($this->ticket->getWatchers() as $watcher) {
                        // Watcher notification preference
                        $watcherPreference = $watcher->getPreference('notifications.email.watcher', true);

                        if (!in_array($watcher, $users) && ($watcherPreference === true || $watcherPreference === null)) {
                            $users[] = $watcher;
                        }
                    }

                    foreach ($users as $user) {
                        // Avoid current user to receive the email
                        if ($this->user !== $user) {
                            $messages[] = [
                                'to' => $user,
                                'title' => $title,
                                'subject' => $subject,
                                'content' => sprintf($content, $user->getName()),
                            ];
                        }
                    }

                    break;

                case 'reopen':
                case 'finish':
                    $users = [];
                    $title = 'Chamado %s';
                    $subject = 'Chamado #' . $this->ticket->getNumber() . ' %s';
                    $content = 'Olá, %s.<br><br>' .
                        'O usuário <b>' . $this->user->getName() . '</b> %s o chamado <b>#' . $this->ticket->getNumber() . '</b>. ' .
                        'Para acessá-lo, <a href="' . $this->router->generate('ticket_edit', ['number' => $this->ticket->getNumber()], true) . '">clique aqui</a>.';

                    // Add attendant                
                    if ($this->ticket->getAttendant() !== $this->user) {
                        $attendantPreference = $this->ticket->getAttendant()->getPreference('notifications.email.' . $this->event, true);

                        if ($attendantPreference === true || $attendantPreference === null) {
                            $users[] = $this->ticket->getAttendant();
                        }
                    }

                    // Add ticket's creator wether it is a customer
                    if ($this->ticket->getCreatedBy()->isAdmin() === false) {
                        $creatorPreference = $this->ticket->getCreatedBy()->getPreference('notifications.email.' . $this->event, true);

                        if ($creatorPreference === true || $creatorPreference === null) {
                            $users[] = $this->ticket->getCreatedBy();
                        }
                    }

                    // Watchers
                    foreach ($this->ticket->getWatchers() as $watcher) {
                        // Watcher notification preference
                        $watcherPreference = $watcher->getPreference('notifications.email.watcher', true);

                        if (!in_array($watcher, $users) && ($watcherPreference === true || $watcherPreference === null)) {
                            $users[] = $watcher;
                        }
                    }

                    switch ($this->event) {
                        case 'reopen': 
                            $words = ['title' => 'reaberto', 'subject' => 'reaberto', 'content' => 'reabriu'];
                            break;
                        case 'finish': 
                            $words = ['title' => 'finalizado', 'subject' => 'finalizado', 'content' => 'finalizou'];
                            break;
                    }

                    foreach ($words as $key => $value) {
                        if ($key !== 'content') {
                            ${$key} = sprintf(${$key}, $value);
                        }
                    }

                    foreach ($users as $user) {
                        // Avoid current user to receive the email
                        if ($this->user !== $user) {
                            $messages[] = [
                                'to' => $user,
                                'title' => $title,
                                'subject' => $subject,
                                'content' => sprintf($content, $user->getName(), $words['content']),
                            ];
                        }
                    }
                    break;

                case 'entry':
                    $users = [];
                    $title = 'Nova interação no chamado #' . $this->ticket->getNumber();
                    $subject = 'Nova interação de chamado';
                    $content = 'Olá, %s.<br><br>' .
                        'O usuário <b>' . $this->user->getName() . '</b> atualizou o chamado <b>#' . $this->ticket->getNumber() . '</b>. ' .
                        'Para acessá-lo, <a href="' . $this->router->generate('ticket_edit', ['number' => $this->ticket->getNumber()], true) . '">clique aqui</a>.';

                    // Add attendant                
                    if ($this->ticket->getAttendant() !== $this->user) {
                        $attendantPreference = $this->ticket->getAttendant()->getPreference('notifications.email.entry', true);

                        if ($attendantPreference === true || $attendantPreference === null) {
                            $users[] = $this->ticket->getAttendant();
                        }
                    }

                    // Add ticket's creator wether it is a customer
                    if (!$this->ticket->getCreatedBy()->isAdmin()) {
                        $creatorPreference = $this->ticket->getCreatedBy()->getPreference('notifications.email.entry', true);

                        if ($creatorPreference === true || $creatorPreference === null) {
                            $users[] = $this->ticket->getCreatedBy();
                        }
                    }

                    // Watchers
                    foreach ($this->ticket->getWatchers() as $watcher) {
                        // Watcher notification preference
                        $watcherPreference = $watcher->getPreference('notifications.email.watcher', true);

                        if (!in_array($watcher, $users) && ($watcherPreference === true || $watcherPreference === null)) {
                            $users[] = $watcher;
                        }
                    }

                    foreach ($users as $user) {
                        // Avoid current user to receive the email
                        if ($user !== $this->user) {
                            $messages[] = [
                                'to' => $user,
                                'title' => $title,
                                'subject' => $subject,
                                'content' => sprintf($content, $user->getName()),
                            ];
                        }
                    }
                    break;
            }

            foreach ($messages as $key => $message) {
                $user = $message['to'];

                $email = \Swift_Message::newInstance()
                    ->setSubject($message['subject'])
                    ->setFrom(array($this->container->getParameter('contact_email') => $this->container->getParameter('contact_name')))
                    ->setTo($user->getEmail())
                    ->setBody(
                        $this->container->get('templating')->render(
                            'AppBundle:Email:email.single.html.twig', [
                            'title' => $message['title'],
                            'content' => $message['content'],
                            'ticket' => $this->ticket,
                            ]
                        ), 'text/html'
                    );
                $this->mailer->send($email);
            }
        }
    }
}