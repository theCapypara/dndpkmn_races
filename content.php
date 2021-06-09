<?php
declare(strict_types=1);

use Twig\Error\LoaderError;

class Content
{
    /**
     * @var int|string
     */
    private $headerId;

    /**
     * @var \Twig\Environment
     */
    private $twigEnv;

    /**
     * @var ArrayAccess
     */
    private $race;

    /**
     * @var array
     */
    private $ballPosition;

    const TPL_EXT = '.twig';

    const DEFAULT_CONTAINER = '_base';

    const DEFAULT_LAYOUT = 'base';
    const FIRST_LAYOUT = 'first';
    const MEGA_FIRST_LAYOUT = 'mega_first';
    const MEGA_SECOND_LAYOUT = 'mega_second';

    const DIR_BLOCKS = 'race_sheet_blocks';
    const DIR_CONTAINER = 'container';
    const DIR_LAYOUT = 'layout';
    const DIR_LAYOUT_BLOCKS = '_blocks';

    const EXTRA_PAGE = 'extra_page';

    public function __construct(\Twig\Environment $twigEnv, ArrayAccess $race, $pokePageNum)
    {
        $this->twigEnv = $twigEnv;
        $this->race = $race;
        $this->pokePageNum = $pokePageNum;
        mt_srand(crc32($this->race['_id']));
        $w1 = $h1 = mt_rand(350, 700);
        $w2 = $h2 = mt_rand(350, 700);
        $maxW = 815;
        $maxH = 1055;
        $this->ballPosition = [
            [($maxW - $w1) * (mt_rand(5, 100) / 100), ($maxH - $h1) * (mt_rand(25, 100) / 100), $w1, $h1, mt_rand(0, 360)],
            [($maxW - $w2) * (mt_rand(5, 100) / 100), ($maxH - $h1) * (mt_rand(5, 100) / 100), $w2, $h2, mt_rand(0, 360)]
        ];
        $this->headerId = mt_rand(1, 5);
        if ($this->headerId == 1) {
            $this->headerId = '';
        }
    }

    private static function pathJoin(...$dirParts)
    {
        $p = '';
        foreach ($dirParts as $part) {
            $p .= $part . '/';
        }
        return rtrim($p, '/');
    }

    public function render()
    {
        $content = "";
        $pageNum = 0;
        foreach (explode(':', $this->race['layout']) as $page) {
            $pageContent = "";
            $previousBlockName = null;
            foreach (explode(',', $page) as $block) {
                $blockProperties = explode(';', $block);
                $blockName = $blockProperties[0];
                $blockContainer = self::DEFAULT_CONTAINER;
                if (count($blockProperties) > 1) {
                    $blockContainer =  $blockProperties[1];
                }
                $pageContent .= $this->renderBlock($blockName, $previousBlockName, $blockContainer, $pageNum);
                $previousBlockName = $blockName;
            }
            $content .= $this->renderPage($pageNum, $pageContent);
            $pageNum++;
        }
        if (array_key_exists('extra', $this->race)) {
            foreach (array_chunk($this->race['extra'], 2) as $chunk) {
                $content .= $this->renderExtraPage($pageNum, $chunk);
                $pageNum++;
            }
        }
        return $content;
    }

    public function renderBlock(string $blockName, ?string $previousBlockName, string $blockContainer, int $pageNum)
    {
        return $this->tryRender(self::pathJoin(self::DIR_BLOCKS, self::DIR_CONTAINER, $blockContainer), [
            'previousBlockName' => $previousBlockName,
            'pokemon' => $this->race,
            'content' => $this->tryRender(self::pathJoin(self::DIR_BLOCKS, $blockName), [
                'pokemon' => $this->race
            ])
        ]);
    }

    public function renderPage(int $pageNum, string $pageContent)
    {
        $pageLayout = $this->getLayout($pageNum);
        return $this->tryRender(self::pathJoin(self::DIR_BLOCKS, self::DIR_LAYOUT, $pageLayout), [
            'pokemon' => $this->race,
            'pic1_exists' => file_exists(dirname(__FILE__) . '/assets/poke/'. $this->race['_id'] .'/pic1.png'),
            'pic2_exists' => file_exists(dirname(__FILE__) . '/assets/poke/'. $this->race['_id'] .'/pic2.png'),
            'content' => $pageContent,
            'sheet_mod_class' => $pageNum % 2 == 0 ? 'sheet--odd' : 'sheet--even',
            'sheet_mod_bg_name' => $pageNum % 2 == 0 ? 'page1' : 'page2',
            'ball' => $this->ballPosition,
            'header_crop_id' => $this->headerId,
            'footer' => $this->tryRender(self::pathJoin(self::DIR_BLOCKS, self::DIR_LAYOUT, self::DIR_LAYOUT_BLOCKS, 'footer'), [
                'pokemon' => $this->race,
                'page_num' => substr($this->pokePageNum, 0, -1) . ($pageNum + 1)
            ])
        ]);
    }

    public function renderExtraPage(int $pageNum, array $sections)
    {
        return $this->tryRender(self::EXTRA_PAGE, [
            'pokemon' => $this->race,
            'sheet_mod_class' => $pageNum % 2 == 0 ? 'sheet--odd' : 'sheet--even',
            'sheet_mod_bg_name' => $pageNum % 2 == 0 ? 'page1' : 'page2',
            'ball' => $this->ballPosition[$pageNum % 2],
            'sections' => $sections,
            'footer' => $this->tryRender(self::pathJoin(self::DIR_BLOCKS, self::DIR_LAYOUT, self::DIR_LAYOUT_BLOCKS, 'footer'), [
                'pokemon' => $this->race,
                'page_num' => substr($this->pokePageNum, 0, -1) . ($pageNum + 1)
            ])
        ]);
    }

    private function tryRender(string $tpl, array $data)
    {
        try {
            return $this->twigEnv->render($tpl . self::TPL_EXT, $data);
        } catch (LoaderError $exception) {
            return '';
        }
    }

    private function getLayout(int $pageNum)
    {
        if ($this->race['stage'] == 'mega') {
            switch ($pageNum) {
                case 0:
                    return self::MEGA_FIRST_LAYOUT;
                case 1:
                    return self::MEGA_SECOND_LAYOUT;
                default:
                    return self::DEFAULT_LAYOUT;
            }
        }
        return $pageNum === 0 ? self::FIRST_LAYOUT : self::DEFAULT_LAYOUT;
    }
}
