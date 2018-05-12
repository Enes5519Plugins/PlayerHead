<?php

/*
 *  PlayerHead - a Altay and PocketMine-MP plugin to add player head on server
 *  Copyright (C) 2018 Enes Yıldırım
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

namespace Enes5519\PlayerHead;

use Enes5519\PlayerHead\commands\PHCommand;
use Enes5519\PlayerHead\entities\HeadEntity;
use pocketmine\entity\Entity;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\math\Vector3;
use pocketmine\permission\Permission;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;

class PlayerHead extends PluginBase implements Listener{

	public function onEnable(){
		Entity::registerEntity(HeadEntity::class, true, ["PlayerHead"]);
		$this->getServer()->getCommandMap()->register("playerhead", new PHCommand());
		$this->getServer()->getPluginManager()->addPermission(new Permission("playerhead", null, "op"));
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onPlace(BlockPlaceEvent $event){
		$player = $event->getPlayer();
		if($player->hasPermission("playerhead")){
			$item = $player->getInventory()->getItemInHand();
			if($item->getId() == Item::MOB_HEAD){
				$head = $item->getNamedTag()->getString("Head", "");
				$player = $this->getServer()->getPlayerExact($head);
				if($player != null){
					$this->spawnPlayerHead($player, $event->getBlock());
					if(!$player->isCreative()) $player->getInventory()->clear($player->getInventory()->getHeldItemIndex());
					$event->setCancelled(true);
				}
			}
		}
	}

	public static function spawnPlayerHead(Player $player, Vector3 $pos){
		$nbt = HeadEntity::createBaseNBT($pos->add(0.5, 0, 0.5), null, self::getYaw($pos, $player));
		$nbt->setString("Head", $player->getName());
		$nbt->setByteArray("SkinData", $player->getSkin()->getSkinData());

		$head = new HeadEntity($player->level, $nbt);
		$head->spawnToAll();
	}

	public static function getYaw(Vector3 $pos, Vector3 $target) : float{
		$xDist = $target->x - $pos->x;
		$zDist = $target->z - $pos->z;
		$yaw = atan2($zDist, $xDist) / M_PI * 180 - 90;
		if($yaw < 0){
			$yaw += 360.0;
		}

		return $yaw;
	}

	public static function getPlayerHeadItem(string $name) : Item{
		$item = ItemFactory::get(Item::MOB_HEAD, 3);
		$tag = $item->getNamedTag();
		$tag->setString("Head", $name);
		$item->setNamedTag($tag);
		$item->setCustomName("§r§6$name's Head");
		return $item;
	}

}