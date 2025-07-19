# MaxRectPacker - Efficient 2D Rectangle Packing in PHP

MaxRectPacker is a PHP class that arranges rectangles inside a fixed 2D space (like loading boxes in a truck) using the **Maximal Rectangles algorithm** with:

- Support for **box rotation**  
- Best Short Side Fit heuristic for optimal placement  
- Export layout data for further processing  
- Simple HTML visualization of the packed boxes  

---

## Installation

Just download or clone this repo and include the class:

```php
require_once 'src/MaxRectPacker.php';
