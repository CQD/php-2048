<?php

class Game
{
    const MAX = 2048;

    private $renderer;
    private $map;

    public function __construct()
    {
        $this->map = new Map();
        $this->renderer = new Renderer();
    }

    public function start()
    {
        system('stty cbreak -echo');

        $this->renderer->print($this->map);

        $inputBuffer = [0, 0, 0];

        while (true) {
            while ("\n" === $key = fgetc(STDIN));
            array_shift($inputBuffer);
            array_push($inputBuffer, $key);
            $cmd = $this->bufferToCommand($inputBuffer);
            if (!$cmd) {
                continue;
            }

            $oldMap = $this->map->toArray();

            $moveCommands = [
                'up'    => ['vertical', false],
                'down'  => ['vertical', true],
                'left'  => ['horizontal', false],
                'right' => ['horizontal', true],
            ];
            if (isset($moveCommands[$cmd])) {
                list($direction, $reversed) = $moveCommands[$cmd];
                for ($i = 0; $i < Map::WIDTH; $i++) {
                    $row = $this->map->fetchRow($direction, $i);
                    $row = $reversed ? array_reverse($row) : $row;
                    $row = $this->map->mergeRow($row);
                    $row = $reversed ? array_reverse($row) : $row;
                    $row = $this->map->setRow($direction, $i, $row);
                }
            }

            if ($oldMap === $this->map->toArray()) {
                continue;
            }

            $this->renderer->reset()->print($this->map);
            usleep(150 * 1000);

            $this->map->addBlock();
            $this->renderer->reset()->print($this->map);

            if (!$this->map->moveable()) {
                echo "Can't move, you lose!\n";
                break;
            } elseif ($this->map->max() >= static::MAX) {
                printf("You achieved %d and won the game!\n", static::MAX);
                break;
            }
        }

        system('stty -cbreak echo');
    }

    private function bufferToCommand($inputBuffer)
    {
        switch ($inputBuffer) {
        case ["\033", "[", "A"]:
            return 'up';
        case ["\033", "[", "B"]:
            return 'down';
        case ["\033", "[", "C"]:
            return 'right';
        case ["\033", "[", "D"]:
            return 'left';
        }
        return null;
    }
}

class Map
{
    const WIDTH = 4;

    private $map;

    public function __construct()
    {
        $this->map = [];
        for ($x = 0; $x < Map::WIDTH; $x++) {
            for ($y = 0; $y < Map::WIDTH; $y++) {
                $this->map[$x][$y] = 0;
            }
        }

        $this->addBlock();
        $this->addBlock();
    }

    public function setMap(array $map)
    {
        $this->map = $map;
    }

    public function addBlock()
    {
        $emptyGrids = [];
        for ($x = 0; $x < Map::WIDTH; $x++) {
            for ($y = 0; $y < Map::WIDTH; $y++) {
                if (0 === $this->map[$x][$y]) {
                    $emptyGrids[] = [$x, $y];
                }
            }
        }

        if (!$emptyGrids) {
            return false;
        }

        $idx = array_rand($emptyGrids);
        list($x, $y) = $emptyGrids[$idx];
        $this->map[$x][$y] = 2;

        return true;
    }

    public function mergeRow(array $row)
    {
        $row = array_values(array_filter($row));

        $newRow = [];
        for ($idx = 0; $idx < count($row); $idx++) {
            $val = $row[$idx];
            $nextVal = $row[$idx + 1] ?? null;
            if ($val === $nextVal) {
                $val += $nextVal;
                $idx++;
            }
            $newRow[] = $val;
        }

        $newRow = array_pad($newRow, Map::WIDTH, 0);

        return $newRow;
    }

    public function setRow(string $direction, int $idx, array $values = [])
    {
        $map = [
            'vertical'    => ['y', 'x'],
            'horizontal'  => ['x', 'y'],
        ];

        list ($stator, $mover) = $map[$direction];

        $$stator = $idx;
        $$mover = 0;

        $result = [];
        for ($i = 0; $i < static::WIDTH; $i++) {
            $this->map[$x][$y] = $values[$$mover] ?? $this->map[$x][$y];
            $result[] = $this->map[$x][$y];
            $$mover++;
        }

        return $result;
    }

    public function fetchRow(string $direction, int $idx)
    {
        return $this->setRow($direction, $idx);
    }

    public function moveable()
    {
        for ($x = 0; $x < Map::WIDTH; $x++) {
            for ($y = 0; $y < Map::WIDTH; $y++) {
                $me = $this->map[$x][$y];
                if (0 === $me) {
                    return true;
                }

                $right = $this->map[$x + 1][$y] ?? false;
                $down = $this->map[$x][$y + 1] ?? false;
                if ($me === $right || $me === $down) {
                    return true;
                }
            }
        }

        return false;
    }

    public function max()
    {
        $max = -1;
        foreach ($this->map as $row) {
            $rowMax = max($row);
            $max = max($max, $rowMax);
        }
        return $max;
    }

    public function toArray()
    {
        return $this->map;
    }
}

class Renderer
{
    public function print($map)
    {
        if (!is_array($map)) {
            $map = $map->toArray();
        }

        echo "Game of ", Game::MAX, "\n";
        $hr = str_repeat(' ------', Map::WIDTH) . "\n";
        echo $hr;
        foreach ($map as $y => $row) {
            echo "|";
            foreach ($row as $x => $value) {
                $value = $value ?: '';
                printf(" %4s |",  $value);
            }
            echo "\n", $hr;
        }
    }

    public function reset()
    {
        echo "\033[2K";
        $up = Map::WIDTH * 2 + 1 + 1;
        echo "\033[{$up}A\033[1G";
        return $this;
    }
}
