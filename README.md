# PlayerHead
Gives the head of a player. 

## Commands
- **/playerhead <player-name> : Give player head**

## For Plugin Devs
```php
# Spawn ($player is Player class)
$skin = $player->getSkin(); // skin
$skin = new Skin($player->getName(), $skin->getSkinData(), $skin->getCapeData(), $skin->getGeometryName(), $skin->getGeometryData()); // for item name
PlayerHead::spawnPlayerHead($skin, $player); // Skin - Position - Yaw - Pitch
```

## TODOS
- [ ] Add Config
- [ ] Handle death event for give head

## Screenshot 
<img height=200 src="https://cdn.pbrd.co/images/HkJebcX.png" />