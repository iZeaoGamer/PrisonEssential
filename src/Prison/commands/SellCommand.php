<?php

namespace Prison\commands;

use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;
use Prison\Main;

class SellCommand extends PluginCommand{

    /** @var Main */
    private $plugin;

    public function __construct(string $name, Main $plugin){
        $this->plugin = $plugin;
        parent::__construct($name, $plugin);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
        if($sender instanceof Player){
            if(!in_array($sender->getLevel()->getFolderName(), $this->plugin->getConfig()->get("sell"))){
                $sender->sendMessage($this->plugin->getConfig()->get("not-in-prison-world"));
                return true;
            }
            if(!isset($args[0])){
                $this->plugin->sell($sender, $sender->getInventory()->getItemInHand());
            }else if($args[0] === "all"){
                foreach($sender->getInventory()->getContents() as $item){
                    $this->plugin->sell($sender, $item);
                }
            }
        }
        return true;
    }
}