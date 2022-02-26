<?php

namespace platz1de\EasyEdit\command\defaults\utility;

use platz1de\EasyEdit\brush\BrushHandler;
use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\exception\PatternParseException;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\pattern\parser\ParseError;
use platz1de\EasyEdit\pattern\parser\PatternParser;
use platz1de\EasyEdit\utils\ArgumentParser;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class BrushCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/brush", "Create a new Brush", [KnownPermissions::PERMISSION_BRUSH], "//brush sphere [radius] [pattern]\n//brush smooth [radius]\n//brush naturalize [radius] [topBlock] [middleBlock] [bottomBlock]\n//brush cylinder [radius] [height] [pattern]", ["/br"]);
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		ArgumentParser::requireArgumentCount($args, 1, $this);
		$type = BrushHandler::nameToIdentifier($args[0]);

		$nbt = CompoundTag::create()->setString("brushType", BrushHandler::identifierToName($type));
		switch ($type) {
			case BrushHandler::BRUSH_SPHERE:
				ArgumentParser::parseCombinedPattern($player, $args, 2, "stone");
				$nbt->setFloat("brushSize", (float) ($args[1] ?? 3));
				$nbt->setString("brushPattern", $args[2] ?? "stone");
				break;
			case BrushHandler::BRUSH_SMOOTH:
				$nbt->setFloat("brushSize", (float) ($args[1] ?? 5));
				break;
			case BrushHandler::BRUSH_NATURALIZE:
				try {
					PatternParser::parseInput($args[2] ?? "grass", $player);
					PatternParser::parseInput($args[3] ?? "dirt", $player);
					PatternParser::parseInput($args[4] ?? "stone", $player);
				} catch (ParseError $exception) {
					throw new PatternParseException($exception);
				}
				$nbt->setFloat("brushSize", (float) ($args[1] ?? 4));
				$nbt->setString("topBlock", $args[2] ?? "grass");
				$nbt->setString("middleBlock", $args[3] ?? "dirt");
				$nbt->setString("bottomBlock", $args[4] ?? "stone");
				break;
			case BrushHandler::BRUSH_CYLINDER:
				ArgumentParser::parseCombinedPattern($player, $args, 3, "stone");
				$nbt->setFloat("brushSize", (float) ($args[1] ?? 4));
				$nbt->setShort("brushHeight", (int) ($args[2] ?? 2));
				$nbt->setString("brushPattern", $args[3] ?? "stone");
		}
		$item = VanillaItems::WOODEN_SHOVEL()->setNamedTag($nbt);
		$lore = [];
		foreach ($nbt->getValue() as $name => $value) {
			$lore[] = $name . ": " . $value;
		}
		$item->setLore($lore);
		$item->setCustomName(TextFormat::GOLD . "Brush");
		$player->getInventory()->setItem($player->getInventory()->getHeldItemIndex(), $item);
	}

	public function getCompactHelp(): string
	{
		return "//brush sphere [radius] [pattern] - Create a spherical brush\n//brush smooth [radius] - Create a smoothing brush\n//brush naturalize [radius] [topBlock] [middleBlock] [bottomBlock] - Create a naturalizing brush\n//brush cylinder [radius] [height] [pattern] - Create a cylindrical brush";
	}
}