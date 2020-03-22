<?php

namespace Prison\commands;

use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;
use Prison\Main;

class MystatusCommand extends PluginCommand{

    /** @var Main */
    private $plugin;

    public function __construct(string $name, Main $plugin){
        $this->plugin = $plugin;
        parent::__construct($name, $plugin);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
        if($sender instanceof Player){
            $sender->sendMessage(str_replace(["{rank}", "{prestige}", "{next rank}", "{next prestige}", "{money missing}"], [$this->plugin->getRank($sender), $this->plugin->getPrestige($sender), $this->plugin->getNextRank($sender), $this->plugin->getNextPrestige($sender), $this->plugin->calculateMoney($sender)], $this->plugin->getConfig()->get("mystatus-message")));
        }
        return true;
    }
}