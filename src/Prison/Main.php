<?php

namespace Prison;

use _64FF00\PurePerms\PurePerms;
use falkirks\minereset\Mine;
use falkirks\minereset\MineReset;
use onebone\economyapi\EconomyAPI;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use Prison\commands\MystatusCommand;
use Prison\commands\RankupCommand;
use Prison\commands\SellCommand;
use Prison\commands\SetprestigeCommand;
use Prison\commands\SetrankCommand;
use pocketmine\utils\Config;

class Main extends PluginBase{

    /** @var array */
    private $ranks = [];
    /** @var array */
    private $blocks = [];
    /** @var array */
    private $prestiges = [];//index => ["tag" => tag, "money" => money]
    /** @var array */
    private $players = [];//name => ["rank" => rank, "prestige" => prestige]

    public function onEnable() : void{
		@mkdir($this->getDataFolder());
        $this->saveDefaultConfig();
        $this->ranks = $this->getConfig()->get("ranks");
        $this->blocks = $this->getConfig()->get("blocks");
        $this->prestiges[0] = ["tag" => $this->getNoPrestigeTag(), "money" => 0];
        $configPrestiges = $this->getConfig()->get("prestiges");
        $index = 1;
        foreach($configPrestiges as $prestige => $money){
            $this->prestiges[$index] = ["tag" => $prestige, "money" => $money];
            $index++;
        }
        if(!file_exists($this->getDataFolder() . "players.json")){
            file_put_contents($this->getDataFolder() . "players.json", json_encode([]));
        }
        $this->players = json_decode(file_get_contents($this->getDataFolder() . "players.json"), true);
        $this->getServer()->getCommandMap()->registerAll("prison", [
            new MystatusCommand("mystat", $this),
            new RankupCommand("rankup", $this),
            new SellCommand("msell", $this),
            new SetprestigeCommand("setprestige", $this),
            new SetrankCommand("setrank", $this)
        ]);
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
    }

    public function onDisable(){
        file_put_contents($this->getDataFolder() . "players.json", json_encode($this->players));
    }
	
	public function translateColors($string){
		$msg = str_replace("&1",TextFormat::DARK_BLUE,$string);
		$msg = str_replace("&2",TextFormat::DARK_GREEN,$msg);
		$msg = str_replace("&3",TextFormat::DARK_AQUA,$msg);
		$msg = str_replace("&4",TextFormat::DARK_RED,$msg);
		$msg = str_replace("&5",TextFormat::DARK_PURPLE,$msg);
		$msg = str_replace("&6",TextFormat::GOLD,$msg);
		$msg = str_replace("&7",TextFormat::GRAY,$msg);
		$msg = str_replace("&8",TextFormat::DARK_GRAY,$msg);
		$msg = str_replace("&9",TextFormat::BLUE,$msg);
		$msg = str_replace("&0",TextFormat::BLACK,$msg);
		$msg = str_replace("&a",TextFormat::GREEN,$msg);
		$msg = str_replace("&b",TextFormat::AQUA,$msg);
		$msg = str_replace("&c",TextFormat::RED,$msg);
		$msg = str_replace("&d",TextFormat::LIGHT_PURPLE,$msg);
		$msg = str_replace("&e",TextFormat::YELLOW,$msg);
		$msg = str_replace("&f",TextFormat::WHITE,$msg);
		$msg = str_replace("&o",TextFormat::ITALIC,$msg);
		$msg = str_replace("&l",TextFormat::BOLD,$msg);
		$msg = str_replace("&r",TextFormat::RESET,$msg);
		return $msg;
	}
	
    public function registerPlayer(Player $player) : void{
        $name = $player->getLowerCaseName();
        $this->players[$name]["rank"] = $this->getDefaultRank();
        $this->players[$name]["prestige"] = $this->getNoPrestigeTag();
    }

    public function getDefaultRank() : string{
        return array_search(0, $this->ranks);
    }

    public function getNoPrestigeTag() : string{
        return $this->getConfig()->get("no-prestige-tag");
    }

    public function setRank(Player $player, string $rank) : void{
        $this->players[$player->getLowerCaseName()]["rank"] = $rank;
    }

    public function getRank(Player $player) : string{
        return $this->players[$player->getLowerCaseName()]["rank"];
    }

    public function rankExists(string $rank) : bool{
        return isset($this->ranks[$rank]);
    }

    public function getRanks() : array{
        return $this->ranks;
    }

    public function setPrestige(Player $player, string $prestige) : void{
        $this->players[$player->getLowerCaseName()]["prestige"] = $prestige;
    }

    public function getPrestige(Player $player) : string{
        return $this->players[$player->getLowerCaseName()]["prestige"];
    }

    public function getPrestigeIndex(Player $player) : int{
        $key = $this->getPrestige($player);
        foreach($this->prestiges as $index => $prestige){
            if($key === $prestige["tag"]) return $index;
        }
        return 0;
    }

    public function getPrestiges() : array{
        return $this->prestiges;
    }

    public function prestigeExists(string $prestige) : bool{
        return isset($this->prestiges[$prestige]);
    }

    public function playerExists(Player $player) : bool{
        return isset($this->players[$player->getLowerCaseName()]);
    }

    public function sell(Player $player, Item $item) : void{
        $sum = 0;
        $i = "" . $item->getId() . ":" . $item->getDamage();
        if(isset($this->blocks[$i])){
            $sum += $this->blocks[$i] * $item->getCount() * $this->getMultiplier($player);
            $player->getInventory()->removeItem($item);
        }
        $this->getEconomyAPI()->addMoney($player, $sum);
        $player->sendMessage(str_replace("{money}", $sum, $this->getConfig()->get("money-gained")));
    }

    public function rankup(Player $player) : bool{
        if($this->getNextRank($player) !== ""){
            $next = $this->getNextRank($player);
            if($this->getEconomyAPI()->myMoney($player) >= $this->ranks[$next]){
                $this->getEconomyAPI()->reduceMoney($player, $this->ranks[$next]);
                $this->players[strtolower($player->getName())]["rank"] = $next;
                $player->sendMessage(str_replace("{rank}", $next, $this->getConfig()->get("rankup-message")));
                $this->getServer()->broadcastMessage(str_replace(["{name}", "{rank}"], [$player->getName(), $next], $this->getConfig()->get("rankup-broadcast")));
                $this->getPurePerms()->getUserDataMgr()->setPermission($player, strtolower(str_replace("{rank}", $next, $this->getConfig()->get("permission"))));
                return true;
            }
        }else{
            $next = $this->getNextPrestige($player);
            if($next !== "" and $this->getEconomyAPI()->myMoney($player) >= $this->getPrestigeMoney($next)){
                $this->players[$player->getLowerCaseName()]["rank"] = "A";
                $this->players[$player->getLowerCaseName()]["prestige"] = $next;
                $player->sendMessage(str_replace("{rank}", $next, $this->getConfig()->get("rankup-message")));
                if($this->getConfig()->get("reset-money-on-prestige")){
                    $player->sendMessage($this->getConfig()->get("reset-money-message"));
                    $this->getEconomyAPI()->reduceMoney($player, $this->getEconomyAPI()->myMoney($player));
                }else{
                    $this->getEconomyAPI()->reduceMoney($player, $this->getPrestigeMoney($next));
                }
                $this->getServer()->broadcastMessage(str_replace(["{name}", "{prestige}"], [$player->getName(), $next], $this->getConfig()->get("prestige-broadcast")));
                $this->removeAllPermissions($player->getName());
                return true;
            }
        }
        return false;
    }

    public function getNextRank(Player $player) : string{
        $key = $this->getRank($player);
        $next = false;
        foreach($this->ranks as $rank => $price){
            if($next) return $rank;
            if($rank === $key) $next = true;
        }
        return "";
    }

    public function getNextPrestige(Player $player) : string{
        $key = $this->getPrestige($player);
        $next = false;
        foreach($this->prestiges as $index => $prestige){
            if($next) return $prestige["tag"];
            if($prestige["tag"] === $key) $next = true;
        }
        return "";
    }

    public function getPrestigeMoney(string $name) : int{
        foreach($this->prestiges as $index => $prestige){
            if($prestige["tag"] === $name) return $prestige["money"];
        }
        return 0;
    }

    public function calculateMoney(Player $player) : int{
        if($this->getNextRank($player) === ""){
            if(isset($this->players[$player->getLowerCaseName()]["prestige"])){
                return $this->getNextPrestige($player) !== "" ? $this->prestiges[$this->getNextPrestige($player)]["money"] : 0;
            }
        }
        return $this->ranks[$this->getNextRank($player)];
    }

    public function removeAllPermissions(string $player) : void{
        foreach($this->ranks as $rank => $price){
            if($rank !== $this->getDefaultRank()) $this->getPurePerms()->getUserDataMgr()->unsetPermission($this->getPurePerms()->getPlayer($player), strtolower(str_replace("{rank}", $rank, $this->getConfig()->get("permission"))));
        }
    }

    public function getBaseMultiplier() : float {
        return (float)$this->getConfig()->get("multiplier-base");
    }

    public function getMultiplier(Player $player) : float{
        return $this->getBaseMultiplier() + ($this->getPrestige($player) !== $this->getNoPrestigeTag() ? $this->getPrestigeIndex($player) * (float)$this->getConfig()->get("multiplier-increase") : 0);
    }

    public function getMineByPosition(Position $position) : ?Mine{
        foreach($this->getMineReset()->getMineManager()->getMines() as $mine){
            if($mine->isPointInside($position)) return $mine;
        }
        return null;
    }

    public function getMineReset() : MineReset{
        return $this->getServer()->getPluginManager()->getPlugin("MineReset");
    }

    public function getPurePerms() : PurePerms{
        return $this->getServer()->getPluginManager()->getPlugin("PurePerms");
    }

    public function getEconomyAPI() : EconomyAPI{
        return $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
    }
}