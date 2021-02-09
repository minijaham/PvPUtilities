<?php

declare(strict_types=1);

namespace minijaham\PvPUtilities;

use pocketmine\plugin\PluginBase;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\plugin\Plugin;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as C;
use pocketmine\event\Listener;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerDeathEvent;

use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;

class Main extends PluginBase implements Listener
{
    private $clicks;
    
    public $db;

    public $config
    
    public function onEnable()
    {
        /* Resources */
        @mkdir($this->getDataFolder());
        $this->saveResource("db.yml");
        $this->saveResource("config.yml");
        $this->db = new Config($this->getDataFolder() . "db.yml", Config::YAML);
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
        /* Event */
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }
    
    public function onJoin(PlayerJoinEvent $event)
    {
        $player = $event->getPlayer();
        /* Register Player */
        $this->db->set($player->getName(), 0);
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
    
    public function getCPS(Player $player): int{
		if(!isset($this->clicks[$player->getLowerCaseName()])){
			return 0;
		}
		$time = $this->clicks[$player->getLowerCaseName()][0];
		$clicks = $this->clicks[$player->getLowerCaseName()][1];
		if($time !== time()){
			unset($this->clicks[$player->getLowerCaseName()]);
			return 0;
		}
		return $clicks;
	}
	
	public function addCPS(Player $player): void{
		if(!isset($this->clicks[$player->getLowerCaseName()])){
			$this->clicks[$player->getLowerCaseName()] = [time(), 0];
		}
		$time = $this->clicks[$player->getLowerCaseName()][0];
		$clicks = $this->clicks[$player->getLowerCaseName()][1];
		if($time !== time()){
			$time = time();
			$clicks = 0;
		}
		$clicks++;
		$this->clicks[$player->getLowerCaseName()] = [$time, $clicks];
	}

	public function onDataPacketReceive(DataPacketReceiveEvent $event){
		$player = $event->getPlayer();
		$packet = $event->getPacket();
		if($packet instanceof InventoryTransactionPacket){
			$transactionType = $packet->transactionType;
			if($transactionType === InventoryTransactionPacket::TYPE_USE_ITEM || $transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY){
				$this->addCPS($player);
			}
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
            
            $damager->sendTip(["{cps}", "{combo}", "{reach}"], [$this->getCps($damager), $this->db->get($damager->getName()), round($damager->distance($entity))], $this->config["display"]);
	    // $damager->sendTip("CPS: ".$this->getCps($damager)." | Combo: ".$this->db->get($damager->getName())." | Reach: ".round($damager->distance($entity))); // Count for combo / reach
        }
    }
}
