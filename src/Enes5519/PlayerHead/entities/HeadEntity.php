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

namespace Enes5519\PlayerHead\entities;

use Enes5519\PlayerHead\PlayerHead;
use pocketmine\entity\Entity;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\level\Level;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerSkinPacket;
use pocketmine\Player;
use pocketmine\utils\UUID;

class HeadEntity extends Entity{

	public const HEAD_GEOMETRY = '{"geometry.player_head":{"texturewidth":64,"textureheight":64,"bones":[{"name":"head","pivot":[0,24,0],"cubes":[{"origin":[-4,0,-4],"size":[8,8,8],"uv":[0,0]}]}]}}';

	/** @var float */
	public $height = 0.8;
	/** @var float */
	public $width = 0.6;
	/** @var Skin */
	protected $skin;
	/** @var UUID */
	protected $uuid;

	public function __construct(Level $level, CompoundTag $nbt){
		parent::__construct($level, $nbt);
		$this->setSkin(new Skin($nbt->getString("Head"), $nbt->getByteArray("SkinData")), false);
		$this->uuid = UUID::fromData((string) $this->getId(), $this->skin->getSkinData(), "");
	}

	protected function initEntity() : void{
		$this->setMaxHealth(1);
		$this->setHealth(1);
		parent::initEntity();
	}

	protected function sendSpawnPacket(Player $player) : void{
		if(!$this->skin->isValid()){
			throw new \InvalidStateException("HeadEntity must have a valid skin set");
		}

		$pk = new AddPlayerPacket();
		$pk->uuid = $this->getUniqueId();
		$pk->username = "";
		$pk->entityRuntimeId = $this->getId();
		$pk->position = $this->asVector3();
		$pk->motion = $this->getMotion();
		$pk->yaw = $this->yaw;
		$pk->pitch = $this->pitch;
		$pk->item = ItemFactory::get(Item::AIR);
		$pk->metadata = $this->propertyManager->getAll();
		$player->dataPacket($pk);

		if(!($this instanceof Player)){
			$this->sendSkin([$player]);
		}
	}

	public function setSkin(Skin $skin, bool $send = true) : void{
		if(!$skin->isValid()){
			throw new \InvalidStateException("Specified skin is not valid, must be 8KiB or 16KiB");
		}

		$this->skin = new Skin($skin->getSkinId(), $skin->getSkinData(), "", "geometry.player_head", self::HEAD_GEOMETRY);
		$this->skin->debloatGeometryData();
		if($send) $this->sendSkin();
	}

	public function getSkin() : Skin{
		return $this->skin;
	}

	public function sendSkin(array $targets = null) : void{
		$pk = new PlayerSkinPacket();
		$pk->uuid = $this->getUniqueId();
		$pk->skin = $this->skin;
		$this->server->broadcastPacket($targets ?? $this->hasSpawned, $pk);
	}

	public function hasMovementUpdate() : bool{
		return false;
	}

	public function getUniqueId() : UUID{
		return $this->uuid;
	}

	public function attack(EntityDamageEvent $source) : void{
		$attack = true;
		if($source instanceof EntityDamageByEntityEvent){
			$damager = $source->getDamager();
			if($damager instanceof Player){
				$attack = $damager->hasPermission("playerhead.attack");
			}
		}

		if($attack) parent::attack($source);
	}

	public function saveNBT() : void{
		parent::saveNBT();

		$this->namedtag->setTag(new ByteArrayTag("SkinData", $this->skin->getSkinData()));
		$this->namedtag->setTag(new StringTag("Head", $this->skin->getSkinId()));
	}

	public function getDrops() : array{
		return [PlayerHead::getPlayerHeadItem($this->getSkin(), $this->getSkin()->getSkinId())];
	}

}