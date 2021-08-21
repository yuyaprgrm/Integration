<?php

namespace integration;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;

class EventListener implements Listener
{

    private Main $plugin;

    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
    }

    public function onMessage(PlayerChatEvent $event)
    {
        $player = $event->getPlayer();
        $message = $event->getMessage();
        $this->plugin->postMessage($player->getName(), $message);
    }
}