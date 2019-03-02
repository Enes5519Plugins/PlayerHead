# PlayerHead  
Gives the head of a player.   
  
## Commands  
- **/playerhead [playerName] : Give player head** 
  
## For Developers 
- Spawn  
```php
$nbt = EntityFactory::createBaseNBT($player->add(0.5, 0, 0.5));  
$nbt->setTag(PlayerHead::skinToTag($player->getSkin(), $player->getName()));  
(EntityFactory::create(HeadEntity::class, $player->level, $nbt))->spawnToAll();  
```  
  
## Screenshot 
<img height=200 src="https://minecraftcommand.science//system/blogs/avatars/000/000/004/medium/custom_heads.png" />