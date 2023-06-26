<?php

declare(strict_types=1);

namespace FloatingMessage\Jorgebyte;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\event\player\PlayerChatEvent;

use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\Config;

class Main extends PluginBase implements Listener
{
    /** @var Config */
    private $config;

    /** @var array */
    private $enabledPlayers;

    /** @var array */
    private $playerMessages;

    public function onEnable(): void
    {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML, [
            'textLimit' => 25,
            'messageDuration' => 8
        ]);
        $this->enabledPlayers = [];
        $this->playerMessages = [];
    }

    /**
     * Handle the command execution for /floatingmessage
     *
     * @param CommandSender $sender
     * @param Command $command
     * @param string $label
     * @param array $args
     * @return bool
     */
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        if ($command->getName() === "floatingmessage") {
            if (count($args) === 0) {
                $sender->sendMessage("Usage: /floatingmessage <on|off>");
                return true;
            }

            $subCommand = strtolower($args[0]);

            switch ($subCommand) {
                case "on":
                    $this->enabledPlayers[$sender->getName()] = true;
                    $sender->sendMessage("floating message enabled.");
                    break;
                case "off":
                    unset($this->enabledPlayers[$sender->getName()]);
                    unset($this->playerMessages[$sender->getName()]);
                    $sender->sendMessage("floating message disabled.");
                    break;
                default:
                    $sender->sendMessage("Unknown subcommand. Usage: /floatingmessage <on|off>");
                    break;
            }
        }

        return true;
    }

    /**
     * @param PlayerChatEvent $event
     */
    public function onPlayerChat(PlayerChatEvent $event): void
    {
        $player = $event->getPlayer();
        $message = $event->getMessage();

        $textLimit = $this->config->get('textLimit');

        $ofText = strlen($message) > $textLimit;

        if (isset($this->enabledPlayers[$player->getName()])) {
            if ($ofText) {
                $formattedMessage = substr($message, 0, $textLimit) . '...';
            } else {
                $formattedMessage = $message;
            }

            $player->setNameTag($player->getName() . "\n" . $formattedMessage);

            $duration = $this->config->get('messageDuration');
            $this->playerMessages[$player->getName()] = $formattedMessage;
            #task
            $this->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player) {
                unset($this->playerMessages[$player->getName()]);
                $player->setNameTag($player->getName());
            }), $duration * 20);
        } else {
            $event->setMessage($message);
        }
    }
}
