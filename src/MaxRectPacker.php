<?php

class MaxRectPacker {
    private $width;
    private $height;
    private $freeRectangles = [];
    private $usedRectangles = [];

    public function __construct($width, $height) {
        $this->width = $width;
        $this->height = $height;
        $this->freeRectangles = [['x' => 0, 'y' => 0, 'width' => $width, 'height' => $height]];
    }

    public function addBoxes(array $boxes) {
        $placed = [];

        foreach ($boxes as $box) {
            $pos = $this->findPositionForNewNodeBestAreaFit($box['width'], $box['height']);
            if ($pos !== null) {
                $rect = [
                    'x' => $pos['x'],
                    'y' => $pos['y'],
                    'width' => $box['width'],
                    'height' => $box['height'],
                    'label' => $box['label']
                ];
                $placed[] = $rect;
                $this->placeRectangle($rect);
            } else {
                // Can't place this box
                // TO DO: handle this (e.g. add to 'not placed' list)
            }
        }

        return $placed;
    }

    private function findPositionForNewNodeBestAreaFit($width, $height) {
        $bestNode = null;
        $bestAreaFit = PHP_INT_MAX;

        foreach ($this->freeRectangles as $freeRect) {
            if ($freeRect['width'] >= $width && $freeRect['height'] >= $height) {
                $areaFit = $freeRect['width'] * $freeRect['height'] - $width * $height;
                if ($areaFit < $bestAreaFit) {
                    $bestNode = ['x' => $freeRect['x'], 'y' => $freeRect['y']];
                    $bestAreaFit = $areaFit;
                }
            }
            // Try rotating box
            if ($freeRect['width'] >= $height && $freeRect['height'] >= $width) {
                $areaFit = $freeRect['width'] * $freeRect['height'] - $height * $width;
                if ($areaFit < $bestAreaFit) {
                    $bestNode = ['x' => $freeRect['x'], 'y' => $freeRect['y']];
                }
            }
        }

        return $bestNode;
    }

    private function placeRectangle($rect) {
        $count = count($this->freeRectangles);
        for ($i = 0; $i < $count; $i++) {
            if ($this->splitFreeNode($this->freeRectangles[$i], $rect)) {
                array_splice($this->freeRectangles, $i, 1);
                $i--;
                $count--;
            }
        }
        $this->pruneFreeList();
        $this->usedRectangles[] = $rect;
    }

    private function splitFreeNode($freeNode, $usedNode) {
        // Check if freeNode and usedNode intersect
        if ($usedNode['x'] >= $freeNode['x'] + $freeNode['width'] ||
            $usedNode['x'] + $usedNode['width'] <= $freeNode['x'] ||
            $usedNode['y'] >= $freeNode['y'] + $freeNode['height'] ||
            $usedNode['y'] + $usedNode['height'] <= $freeNode['y']) {
            return false;
        }

        // Split freeNode into up to 4 smaller rectangles (maximal rectangles)
        if ($usedNode['x'] > $freeNode['x']) {
            $this->freeRectangles[] = [
                'x' => $freeNode['x'],
                'y' => $freeNode['y'],
                'width' => $usedNode['x'] - $freeNode['x'],
                'height' => $freeNode['height']
            ];
        }

        if ($usedNode['x'] + $usedNode['width'] < $freeNode['x'] + $freeNode['width']) {
            $this->freeRectangles[] = [
                'x' => $usedNode['x'] + $usedNode['width'],
                'y' => $freeNode['y'],
                'width' => $freeNode['x'] + $freeNode['width'] - ($usedNode['x'] + $usedNode['width']),
                'height' => $freeNode['height']
            ];
        }

        if ($usedNode['y'] > $freeNode['y']) {
            $this->freeRectangles[] = [
                'x' => $freeNode['x'],
                'y' => $freeNode['y'],
                'width' => $freeNode['width'],
                'height' => $usedNode['y'] - $freeNode['y']
            ];
        }

        if ($usedNode['y'] + $usedNode['height'] < $freeNode['y'] + $freeNode['height']) {
            $this->freeRectangles[] = [
                'x' => $freeNode['x'],
                'y' => $usedNode['y'] + $usedNode['height'],
                'width' => $freeNode['width'],
                'height' => $freeNode['y'] + $freeNode['height'] - ($usedNode['y'] + $usedNode['height'])
            ];
        }

        return true;
    }

    private function pruneFreeList() {
        // Remove any free rectangle fully contained in another
        for ($i = 0; $i < count($this->freeRectangles); $i++) {
            for ($j = $i + 1; $j < count($this->freeRectangles); $j++) {
                if ($this->isContainedIn($this->freeRectangles[$i], $this->freeRectangles[$j])) {
                    array_splice($this->freeRectangles, $i, 1);
                    $i--;
                    break;
                }
                if ($this->isContainedIn($this->freeRectangles[$j], $this->freeRectangles[$i])) {
                    array_splice($this->freeRectangles, $j, 1);
                    $j--;
                }
            }
        }
    }

    private function isContainedIn($a, $b) {
        return $a['x'] >= $b['x'] &&
               $a['y'] >= $b['y'] &&
               $a['x'] + $a['width'] <= $b['x'] + $b['width'] &&
               $a['y'] + $a['height'] <= $b['y'] + $b['height'];
    }

    public function renderHtml() {
        $html = '<div style="position:relative; width:' . $this->width . 'px; height:' . $this->height . 'px; border:2px solid #333; background:#fafafa;">';

        foreach ($this->usedRectangles as $rect) {
            $html .= '<div style="
                position:absolute;
                left:' . $rect['x'] . 'px;
                top:' . $rect['y'] . 'px;
                width:' . $rect['width'] . 'px;
                height:' . $rect['height'] . 'px;
                background:#e74c3c;
                border:1px solid #c0392b;
                box-sizing:border-box;
                color:#fff;
                font-size:12px;
                text-align:center;
                line-height:' . $rect['height'] . 'px;
                overflow:hidden;
                white-space: nowrap;
                text-overflow: ellipsis;
                "
            >' . htmlspecialchars($rect['label']) . '</div>';
        }

        $html .= '</div>';
        return $html;
    }

    public static function test() {
        $packer = new self(500, 300);

        $boxes = [
            ['width' => 100, 'height' => 100, 'label' => 'Box 1'],
            ['width' => 200, 'height' => 50,  'label' => 'Box 2'],
            ['width' => 50,  'height' => 150, 'label' => 'Box 3'],
            ['width' => 120, 'height' => 80,  'label' => 'Box 4'],
            ['width' => 80,  'height' => 120, 'label' => 'Box 5'],
            ['width' => 300, 'height' => 50,  'label' => 'Box 6'],
            ['width' => 150, 'height' => 150, 'label' => 'Box 7'],
            ['width' => 50,  'height' => 50,  'label' => 'Small 1'],
            ['width' => 50,  'height' => 50,  'label' => 'Small 2'],
            ['width' => 50,  'height' => 50,  'label' => 'Small 3'],
            ['width' => 50,  'height' => 50,  'label' => 'Small 4'],
            ['width' => 50,  'height' => 50,  'label' => 'Small 5'],
            ['width' => 50,  'height' => 50,  'label' => 'Small 6'],
            ['width' => 50,  'height' => 50,  'label' => 'Small 7'],
            ['width' => 50,  'height' => 50,  'label' => 'Small 8'],
        ];

        $placed = $packer->addBoxes($boxes);
        echo '<p>Placed boxes: ' . count($placed) . '</p>';
        echo $packer->renderHtml();
    }
}
