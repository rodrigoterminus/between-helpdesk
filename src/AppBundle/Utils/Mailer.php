<?php

namespace AppBundle\Utils;

use \Doctrine\Common\Collections\ArrayCollection;

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
     * User to be notified
     * 
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    private $users;
    
    public function __construct($container) {
        $this->container = $container;
        $this->mailer = $this->container->get('mailer');
        $this->router = $this->container->get('router');
        $this->currentUser = $this->container->get('security.token_storage')->getToken()->getUser();
    }
    
    /**
     * Send emails
     * 
     * @return boolean
     */
    public function send() {
        if ($this->ticket === null) {
            throw new \Exception('Ticket not defined.');
        } elseif ($this->event === null) {
            throw new \Exception('You must defined which event should be notified.');
        } else {
            $messages = [];
            
            switch ($this->event) {
                case 'take':
                    foreach ($this->users as $user) {                        
                        // Watcher notification preference
                        $watcherPreference = $user->getPreference('notifications.email.watcher', true);

                        if ($watcherPreference === true || $watcherPreference === null) {
                            $messages[] = [
                                'to' => $user,
                                'title' => 'Chamado #' . $this->ticket->getNumber() . ' assumido',
                                'subject' => 'Chamado #' . $this->ticket->getNumber() . ' assumido',
                                'content' => 'Olá, ' . $user->getName() . '.<br><br>' .
                                'O usuário <b>' . $this->currentUser->getName() . '</b> assumiu o chamado <b>#' . $this->ticket->getNumber() . '</b>. ' .
                                'Para acessá-lo, <a href="' . $this->router->generate('ticket_edit', ['number' => $this->ticket->getNumber()], true) . '">clique aqui</a>.',
                            ];
                        }                           
                    }
                    break;
                
                case 'transfer':
                    foreach ($this->users as $user) {
                        if ($user === $this->ticket->getAttendant()) {
                            $attendantPreference = $this->ticket->getAttendant()->getPreference('notifications.email.transfer', true);

                            if ($attendantPreference === true || $attendantPreference === null) {
                                $messages[] = [
                                    'to' => $user,
                                    'title' => 'Transferência de chamado',
                                    'subject' => 'Chamado #'. $this->ticket->getNumber() .' transferido para você',
                                    'content' => 'Olá, ' . $user->getName() . '.<br><br>' .
                                        'O usuário <b>' . $this->currentUser->getName() . '</b> transferiu o chamado <b>#' . $this->ticket->getNumber() . '</b> para você. ' .
                                        'Para acessá-lo, <a href="' . $this->router->generate('ticket_edit', ['number' => $this->ticket->getNumber()], true) . '">clique aqui</a>.',
                                ];
                            }
                        } else {
                            // Watcher notification preference
                            $watcherPreference = $user->getPreference('notifications.email.watcher', true);

                            if ($watcherPreference === true || $watcherPreference === null) {
                                $messages[] = [
                                    'to' => $user,
                                    'title' => 'Chamado #' . $this->ticket->getNumber() . ' transferido',
                                    'subject' => 'Chamado #' . $this->ticket->getNumber() . ' transferido',
                                    'content' => 'Olá, ' . $user->getName() . '.<br><br>' .
                                    'O usuário <b>' . $this->currentUser->getName() . '</b> transferiu o chamado <b>#' . $this->ticket->getNumber() . '</b> para o usuário <b>' . $this->ticket->getAttendant()->getName() . '</b>. ' .
                                    'Para acessá-lo, <a href="' . $this->router->generate('ticket_edit', ['number' => $this->ticket->getNumber()], true) . '">clique aqui</a>.',
                                ];
                            }
                        }                           
                    }
                    break;

                case 'comment':
                    $baseMessage = [
                        'title' => 'Novo comentário',
                        'subject' => 'Novo comentário no chamado #'. $this->ticket->getNumber(),
                        'content' => 'Olá, %s.<br><br>' .
                            'O usuário <b>' . $this->currentUser->getName() . '</b> inseriu um novo comentário no chamado <b>#' . $this->ticket->getNumber() . '</b>. ' .
                            'Para acessá-lo, <a href="' . $this->router->generate('ticket_edit', ['number' => $this->ticket->getNumber()], true) . '">clique aqui</a>.',
                    ];
                    
                    foreach ($this->users as $user) {
                        $message = $baseMessage;
                        $message['to'] = $user;
                        $message['content'] = sprintf($message['content'], $user->getName());
                            
                        if ($user === $this->ticket->getAttendant()) {
                            $userPreference = $user->getPreference('notifications.email.comment', true);
                            
                            if ($userPreference === true || $userPreference === null) {                                
                                $messages[] = $message;
                            }
                        } else {
                            $watcherPreference = $user->getPreference('notifications.email.watcher', true);

                            if ($watcherPreference === true || $watcherPreference === null) {
                                $messages[] = $message;
                            }
                        }
                    }
                    break;

                case 'reopen':
                case 'finish':
                    $baseMessage = [
                        'title' => 'Chamado %s',
                        'subject' => 'Chamado #' . $this->ticket->getNumber() . ' %s',
                        'content' => 'Olá, %s.<br><br>' .
                            'O usuário <b>' . $this->currentUser->getName() . '</b> %s o chamado <b>#' . $this->ticket->getNumber() . '</b>. ' .
                            'Para acessá-lo, <a href="' . $this->router->generate('ticket_edit', ['number' => $this->ticket->getNumber()], true) . '">clique aqui</a>.',
                    ];
                    
                    switch ($this->event) {
                        case 'reopen': 
                            $words = ['title' => 'reaberto', 'subject' => 'reaberto', 'content' => 'reabriu'];
                            break;
                        case 'finish': 
                            $words = ['title' => 'finalizado', 'subject' => 'finalizado', 'content' => 'finalizou'];
                            break;
                    }
                    
                    foreach ($this->users as $user) {
                        $message['to'] = $user;
                        $message['title'] = sprintf($baseMessage['title'], $words['title']);
                        $message['subject'] = sprintf($baseMessage['subject'], $words['subject']);
                        $message['content'] = sprintf($baseMessage['content'], $user->getName(), $words['content']);
                            
                        if ($this->event === 'finish' && $user->isAdmin() === false) {
                            $message['content'] .= '<p><b>Importante:</b> Não se esqueça de avaliar este chamado, '.
                                'informando-nos se sua solicitação foi atendida e dando uma nota para este atendimento.</p>';
                        }
                        
                        if ($user === $this->ticket->getAttendant() || $user === $this->ticket->getCreatedBy()) {
                            $userPreference = $user->getPreference('notifications.email.'. $this->event, true);
                            
                            if ($userPreference === true || $userPreference === null) {                                
                                $messages[] = $message;
                            }
                        } else {
                            $watcherPreference = $user->getPreference('notifications.email.watcher', true);

                            if ($watcherPreference === true || $watcherPreference === null) {
                                $messages[] = $message;
                            }
                        }
                    }
                    break;

                case 'post':
                    $baseMessage = [
                        'title' => 'Nova interação no chamado #' . $this->ticket->getNumber(),
                        'subject' => 'Nova interação de chamado',
                        'content' => 'Olá, %1$s.<br><br>' .
                            'O usuário <b>' . $this->currentUser->getName() . '</b> atualizou o chamado <b>#' . $this->ticket->getNumber() . '</b>. ' .
                            'Para acessá-lo, <a href="' . $this->router->generate('ticket_edit', ['number' => $this->ticket->getNumber()], true) . '">clique aqui</a>.',
                    ];
                    
                    foreach ($this->users as $user) {
                        $message = $baseMessage;
                        $message['to'] = $user;
                        $message['content'] = sprintf($message['content'], $user->getName());
                            
                        if ($user === $this->ticket->getAttendant() || $user === $this->ticket->getCreatedBy()) {
                            $userPreference = $user->getPreference('notifications.email.entry', true);
                            
                            if ($userPreference === true || $userPreference === null) {                                
                                $messages[] = $message;
                            }
                        } else {
                            $watcherPreference = $user->getPreference('notifications.email.watcher', true);

                            if ($watcherPreference === true || $watcherPreference === null) {
                                $messages[] = $message;
                            }
                        }
                    }
                    break;
            }
            
            // Send emails
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
     * Set users
     * 
     * @param ArrayCollection $users
     * @return \AppBundle\Utils\Mailer
     */
    function setUsers($users) 
    {
        $this->users = $users;
        
        return $this;
    }


}