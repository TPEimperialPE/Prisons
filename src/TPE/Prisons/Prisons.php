<?php

declare(strict_types=1);

namespace TPE\Prisons;

use pocketmine\plugin\PluginBase;
use TPE\Prisons\Commands\PrestigeCommand;
use TPE\Prisons\Commands\RankUpCommand;
use TPE\Prisons\Commands\ResetCommand;
use TPE\Prisons\Listener\EventListener;
use TPE\Prisons\Provider\SQLite3;
use TPE\Prisons\Utils\Configuration;
use falkirks\minereset\Mine;
use pocketmine\block\Block;

class Prisons extends PluginBase {

    private static $instance;

    private static $provider;
    
    /** @var \falkirks\minereset\MineReset */
    private $mineReset;

    public function onLoad() {
        self::$instance = $this;
    }

    public function onEnable() {
        $this->checkUpdate();

        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
        $this->getServer()->getCommandMap()->register("Prisons", new RankUpCommand());
        
        if($this->getConfig()->get("prestige") === true) {
            $this->getServer()->getCommandMap()->register("Prisons", new PrestigeCommand());
        }
        
        $this->mineReset = $this->getServer()->getPluginManager()->getPlugin("MineReset");

        self::$provider = new SQLite3();
        self::$provider->initDb();
        
        $this->saveResource("playerlist.yml");
    }
    
    public function onDisable() : void {
        if(isset(self::$provider)) {
            self::$provider->closeDb();
        }
        
        $this->saveResource("playerlist.yml");
    }

    public function checkUpdate() : void {
        if($this->getConfig()->get("version") !== 1.2) {
            $this->getLogger()->notice("Your configuration file is outdated, updating...");
            rename($this->getDataFolder() . "config.yml", $this->getDataFolder() . "old_config.yml");
            $this->saveDefaultConfig();
            $this->getConfig()->reload();
        }
    }

    public static function getProvider() : SQLite3 {
        return self::$provider;
    }

    public static function get() : self {
        return self::$instance;
    }

    public function getRank(string $player) : string {
        return self::getProvider()->getPlayerData($player, "rank");
    }

    public function getPrestige(string $player) : int {
        return self::getProvider()->getPlayerData($player, "ascension");
    }

    public function setRank(string $player, string $rank) : void {
        self::getProvider()->setPlayerData($player, "rank", $rank);
    }

    public function setPrestige(string $player, int $prestigeLevel) : void {
        self::getProvider()->setPlayerData($player, "ascension", $prestigeLevel);
    }
    
    public function isInMine(Block $block) : bool {
        foreach ($this->mineReset->getMineManager() as $mine) {
            /** @var Mine $mine */
            if($mine->isPointInside($block)) {
                return true;
            }
        }
        return false;
    }

}
