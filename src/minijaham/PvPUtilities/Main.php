<?php

declare(strict_types=1);

namespace minijaham\PvPUtilities;

use pocketmine\plugin\PluginBase;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\plugin\Plugin;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as C;
use pocketmine\event\Listener;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerDeathEvent;

class Main extends PluginBase implements Listener
{
    public $db;
    
    public function onEnable()
    {
        /* Resources */
        @mkdir($this->getDataFolder());
        $this->saveResource("db.yml");
        $this->db = new Config($this->getDataFolder() . "db.yml", Config::YAML);
        /* Event */
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }
    
    public function onJoin(PlayerJoinEvent $event)
    {
        $player = $event->getPlayer();
        /* If player has played before */
        if (!$player->hasPlayedBefore())
        {
            /* Register Player */
            $this->db->set($player->getName(), 0);
        }
    }
    
    public function onQuit(PlayerQuitEvent $event)
    {
        $player = $event->getPlayer();
        /* If player has more than 0 combos */
        if ($this->db->get($player->getName()) >= 1)
        {
            /* Reset combo on quit */
            $this->db->set($player->getName(), 0);
        }
    }
    
    public function onPlayerDeath(PlayerDeathEvent $event)
    {
    	$player = $event->getPlayer();
    	/* If player has more than 0 combos */
        if ($this->db->get($player->getName()) >= 1)
        {
            /* Reset combo on quit */
            $this->db->set($player->getName(), 0);
        }
    }
    
    public function registerCombo(EntityDamageEvent $event)
    {
        $entity = $event->getEntity();
        
        // if (!$entity instanceof Player) return;
        
        if ($event instanceof EntityDamageByEntityEvent)
        {
            $damager = $event->getDamager();
            if (!$damager instanceof Player) return;
            if ($event->isCancelled()) return; // Check if hit from safezone/attack cooldown
            $this->db->set($damager->getName(), $this->db->get($damager->getName()) + 1);
            $this->db->set($entity->getName(), 0);
            
            $damager->sendTip("Combo: ".$this->db->get($damager->getName())." | Reach: ".round($damager->distance($entity))); // Count for combo / reach
        }
    }
}