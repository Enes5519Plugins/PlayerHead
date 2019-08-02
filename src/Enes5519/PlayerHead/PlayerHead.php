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
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

class PlayerHead extends PluginBase implements Listener{
	/** @var bool */
	private $dropDeath = false;
	/** @var string */
	private static $headFormat;

	public const PREFIX = TextFormat::BLUE . 'PlayerHead' . TextFormat::DARK_GRAY . '> ';

	public function onEnable() : void{
		$this->saveDefaultConfig();

		$data = $this->getConfig()->getAll();
		$this->dropDeath = $data['drop-on-death'] ?? false;
		self::$headFormat = $data['head-format'] ?? '&r&6%s\'s Head';

		Entity::registerEntity(HeadEntity::class, true, ['PlayerHead']);

		$this->getServer()->getCommandMap()->register('playerhead', new PHCommand($data));
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onPlace(BlockPlaceEvent $event) : void{
		$player = $event->getPlayer();
		if($player->hasPermission('playerhead.spawn') and ($item = $player->getInventory()->getItemInHand())->getId() === Item::MOB_HEAD and ($blockData = $item->getCustomBlockData()) !== null){
			$nbt = Entity::createBaseNBT($event->getBlock()->add(0.5, 0, 0.5), null, self::getYaw($event->getBlock(), $player));
            $blockData->setName('Skin');
			$nbt->setTag($blockData);
            (new HeadEntity($player->level, $nbt))->spawnToAll();
			if(!$player->isCreative()){
				$player->getInventory()->setItemInHand($item->setCount($item->getCount() - 1));
			}
			$event->setCancelled();
		}
	}

	public function onDeath(PlayerDeathEvent $event) : void{
		if($this->dropDeath){
			$drops = $event->getDrops();
			$drops[] = self::getPlayerHeadItem($event->getPlayer()->getSkin(), $event->getPlayer()->getName());
			$event->setDrops($drops);
		}
	}

	private static function getYaw(Vector3 $pos, Vector3 $target) : float{
		$yaw = atan2($target->z - $pos->z, $target->x - $pos->x) / M_PI * 180 - 90;
		if($yaw < 0){
			$yaw += 360.0;
		}

		foreach([45, 90, 135, 180, 225, 270, 315, 360] as $direction){
			if($yaw <= $direction){
				return $direction;
			}
		}

		return $yaw;
	}

	/**
	 * @param Skin $skin
	 * @param string $name
	 * @return Item
	 */
	public static function getPlayerHeadItem(Skin $skin, string $name) : Item{
		return (ItemFactory::get(Item::MOB_HEAD, 3))
			->setCustomBlockData(new CompoundTag('Skin', [
				new StringTag('Name', $name),
				new ByteArrayTag('Data', $skin->getSkinData())
			]))
			->setCustomName(TextFormat::colorize(sprintf(self::$headFormat, $name), '&'));
	}
}