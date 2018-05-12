# PlayerHead
PlayerHead plugin for Altay, pmmp

## Commands
- **/playerhead <player-name> : Give player head**

## For Plugin Devs
```php
# Spawn ($player is Player class)
$skin = $player->getSkin(); // skin
$pos = $player; // spawn position
$name = $player->getName(); // for item
$yaw = 0.0;
$pitch = 0.0;
PlayerHead::spawnPlayerHead($skin, $pos, $name, $yaw, $pitch);
```

## Screenshot 
<img height=200 src="https://cdn.pbrd.co/images/HkJebcX.png" />