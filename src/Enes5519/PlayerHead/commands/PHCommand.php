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

namespace Enes5519\PlayerHead\commands;

use Enes5519\PlayerHead\PlayerHead;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\entity\Skin;
use pocketmine\Player;

class PHCommand extends Command{

	public function __construct(){
		parent::__construct(
			"playerhead",
			"Give a player head",
			"/playerhead <playerName:string>",
			["ph"]
		);

		$this->setPermission("playerhead.give");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$this->testPermission($sender) or !($sender instanceof Player)){
			return true;
		}

		if(empty($args)){
			throw new InvalidCommandSyntaxException();
		}

		$player = $sender->getServer()->getPlayer(implode(" ", $args));
		if($player instanceof Player){
			$sender->getInventory()->addItem(PlayerHead::getPlayerHeadItem(new Skin($player->getName(), $player->getSkin()->getSkinData())));
			$sender->sendMessage("§8» §a{$player->getName()}'s head added in your inventory.");
		}

		return true;
	}
}
