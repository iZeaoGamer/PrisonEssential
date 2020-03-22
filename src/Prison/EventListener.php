<?php

namespace Prison;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;


class EventListener implements Listener{

    /** @var Main */
    private $plugin;

    public function __construct(Main $plugin){
        $this->plugin = $plugin;
    }

    public function onJoin(PlayerJoinEvent $event) : void{
        if(!$this->plugin->playerExists($event->getPlayer())){
            $this->plugin->registerPlayer($event->getPlayer());
        }
    }
	
	public function e_damage(EntityDamageEvent $event) : void{
		if($event->getCause()===EntityDamageEvent::CAUSE_FALL)
		$event->setCancelled();
	}

    public function onBreak(BlockBreakEvent $event) : void{
        if(in_array($event->getPlayer()->getLevel()->getFolderName(), $this->plugin->getConfig()->get("worlds"))){
            if($this->plugin->getMineByPosition($event->getBlock()) === null){
                $event->setCancelled(true);
            }
        }
    }
	
	public function onPlace(BlockPlaceEvent $event) : void{
        if(in_array($event->getPlayer()->getLevel()->getFolderName(), $this->plugin->getConfig()->get("worlds"))){
            if($this->plugin->getMineByPosition($event->getBlock()) === null){
                $event->setCancelled(true);
            }
        }
    }
	
	public function onEDE(EntityDamageEvent $event) : void{
        if(in_array($event->getEntity()->getLevel()->getFolderName(), $this->plugin->getConfig()->get("pvp"))){	
            $event->setCancelled();
		}
    }
	
	
    /**
     * @param PlayerChatEvent $event
     * @priority MONITOR
     * @return void
     */
    public function onChat(PlayerChatEvent $event) : void{
        $event->setFormat(str_replace(["{prestige}", "{rank}", "{default format}"], [$this->plugin->getPrestige($event->getPlayer()), $this->plugin->getRank($event->getPlayer()), $event->getFormat()], $this->plugin->getConfig()->get("chat-format")));
    }
}