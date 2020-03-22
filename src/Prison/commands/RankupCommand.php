<?php

namespace Prison\commands;

use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;
use Prison\Main;

class RankupCommand extends PluginCommand{

    /** @var Main */
    private $plugin;

    public function __construct(string $name, Main $plugin){
        $this->plugin = $plugin;
        parent::__construct($name, $plugin);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
        if($sender instanceof Player){
            if(!$this->plugin->rankup($sender)){
                $sender->sendMessage(str_replace("{money}", $this->plugin->calculateMoney($sender), $this->plugin->getConfig()->get("not-enough-money")));
                return true;
            }
        }
        return true;
    }
}