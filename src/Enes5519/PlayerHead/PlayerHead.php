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
use pocketmine\entity\Skin;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\plugin\PluginBase;

class PlayerHead extends PluginBase implements Listener{

	public function onEnable(){
		Entity::registerEntity(HeadEntity::class, true, ["PlayerHead"]);
		$this->getServer()->getCommandMap()->register("playerhead", new PHCommand());
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onPlace(BlockPlaceEvent $event){
		$player = $event->getPlayer();
		if($player->hasPermission("playerhead.spawn")){
			$item = $player->getInventory()->getItemInHand();
			if($item->getId() == Item::MOB_HEAD){
				$head = $item->getNamedTag()->getString("Head", "");
				$skinData = $item->getNamedTag()->getByteArray("SkinData", "");
				if($skinData != ""){
					$this->spawnPlayerHead(new Skin($head, $skinData), $event->getBlock(), $head, self::getYaw($event->getBlock(), $player));
					if(!$player->isCreative()){
						$item = $player->getInventory()->getItemInHand();
						$item->pop();
						$player->getInventory()->setItemInHand($item);
					}
					$event->setCancelled(true);
				}
			}
		}
	}

	public static function spawnPlayerHead(Skin $skin, Position $pos, string $name = null, float $yaw = null, float $pitch = null) : HeadEntity{
		$nbt = HeadEntity::createBaseNBT($pos->add(0.5, 0, 0.5), null, $yaw ?? 0.0, $pitch ?? 0.0);
		$nbt->setString("Head", $name ?? "Player");
		$nbt->setByteArray("SkinData", $skin->getSkinData());

		$head = new HeadEntity($pos->level, $nbt);
		$head->spawnToAll();

		return $head;
	}

	public static function getYaw(Vector3 $pos, Vector3 $target) : float{
		$xDist = $target->x - $pos->x;
		$zDist = $target->z - $pos->z;
		$yaw = atan2($zDist, $xDist) / M_PI * 180 - 90;
		if($yaw < 0){
			$yaw += 360.0;
		}

		$array = [45, 90, 135, 180, 225, 270, 315, 360];
		foreach($array as $a){
			$min = min($yaw, $a);
			if($min == $yaw){
				return $a;
			}else{
				continue;
			}
		}

		return $yaw;
	}

	public static function getPlayerHeadItem(Skin $skin, string $name = null) : Item{
		$item = ItemFactory::get(Item::MOB_HEAD, 3);
		$tag = $item->getNamedTag();
		$tag->setByteArray("SkinData", $skin->getSkinData());
		$tag->setString("Head", $name);
		$item->setNamedTag($tag);
		$item->setCustomName("§r§6".($name ?? "Player")."'s Head");
		return $item;
	}

}