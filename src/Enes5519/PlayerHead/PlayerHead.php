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
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Skin;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

class PlayerHead extends PluginBase implements Listener{
	/** @var bool */
	public static $dropDeath = false;
	/** @var string */
	public static $headFormat;

	public const PREFIX = TextFormat::BLUE . 'PlayerHead' . TextFormat::DARK_GRAY . '> ';

	public function onEnable() : void{
		$this->saveDefaultConfig();
		$this->loadSettings($this->getConfig()->getAll());

		EntityFactory::register(HeadEntity::class, true, ['PlayerHead']);

		$this->getServer()->getCommandMap()->register('playerhead', new PHCommand());
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function loadSettings(array $data) : void{
		self::$dropDeath = $data['drop-on-death'] ?? false;
		self::$headFormat = $data['head-format'] ?? '&r&6%s\'s Head';
	}

	public function onPlace(BlockPlaceEvent $event) : void{
		$player = $event->getPlayer();
		if($player->hasPermission('playerhead.spawn') and ($item = $player->getInventory()->getItemInHand())->getId() === Item::MOB_HEAD){
			$blockData = $item->getCustomBlockData();
			if($blockData !== null){
				$skin = $blockData->getCompoundTag('Skin');
				if($skin !== null){
					$nbt = EntityFactory::createBaseNBT($player->add(0.5, 0, 0.5), null, self::getYaw($event->getBlock(), $player));
					$nbt->setTag($skin);
					(EntityFactory::create(HeadEntity::class, $player->level, $nbt))->spawnToAll();
					if(!$player->isCreative()){
						$item->pop();
						$player->getInventory()->setItemInHand($item);
					}
					$event->setCancelled();
				}
			}
		}
	}

	public function onDeath(PlayerDeathEvent $event) : void{
		if(self::$dropDeath){
			$drops = $event->getDrops();
			$drops[] = self::getPlayerHeadItem($event->getPlayer()->getSkin(), $event->getPlayer()->getName());
			$event->setDrops($drops);
		}
	}

	public static function getYaw(Vector3 $pos, Vector3 $target) : float{
		$xDist = $target->x - $pos->x;
		$zDist = $target->z - $pos->z;
		$yaw = atan2($zDist, $xDist) / M_PI * 180 - 90;
		if($yaw < 0){
			$yaw += 360.0;
		}

		foreach([45, 90, 135, 180, 225, 270, 315, 360] as $direction){
			$min = min($yaw, $direction);
			if($min === $yaw){
				return $direction;
			}
		}

		return $yaw;
	}

	/**
	 * @param CompoundTag|Skin $skin
	 * @param string|null $name
	 * @return Item
	 */
	public static function getPlayerHeadItem($skin, string $name = null) : Item{
		/** @var CompoundTag $skinTag */
		$skinTag = ($skin instanceof Skin) ? self::skinToTag($skin) : $skin;
		$item = ItemFactory::get(Item::MOB_HEAD, 3);
		$item->setCustomBlockData(new CompoundTag('', [$skinTag]));
		$item->setCustomName(TextFormat::colorize(sprintf(self::$headFormat, $name ?? $skinTag->getString('Name', 'Player')), '&'));
		return $item;
	}

	public static function skinToTag(Skin $skin, string $name = null) : CompoundTag{
		return new CompoundTag('Skin', [
			new StringTag('Name', $name ?? $skin->getSkinId()),
			new ByteArrayTag('Data', $skin->getSkinData())
		]);
	}

	public static function tagToSkin(CompoundTag $tag) : Skin{
		return new Skin(
			$tag->getString('Name'),
			$tag->getByteArray('Data')
		);
	}
}