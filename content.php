<?php
declare(strict_types=1);

use Twig\Error\LoaderError;

class Content
{
    /**
     * @var \Twig\Environment
     */
    private $twigEnv;

    /**
     * @var ArrayAccess
     */
    private $race;

    const TPL_EXT = '.twig';

    const DEFAULT_CONTAINER = '_base';

    const DEFAULT_LAYOUT = 'base';
    const FIRST_LAYOUT = 'first';

    const DIR_BLOCKS = 'race_sheet_blocks';
    const DIR_CONTAINER = 'container';
    const DIR_LAYOUT = 'layout';
    const DIR_LAYOUT_BLOCKS = '_blocks';

    public function __construct(\Twig\Environment $twigEnv, ArrayAccess $race)
    {
        $this->twigEnv = $twigEnv;
        $this->race = $race;
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
        $pageLayout = $pageNum === 0 ? self::FIRST_LAYOUT : self::DEFAULT_LAYOUT;
        return $this->tryRender(self::pathJoin(self::DIR_BLOCKS, self::DIR_LAYOUT, $pageLayout), [
            'pokemon' => $this->race,
            'pic1_exists' => file_exists(dirname(__FILE__) . '/assets/poke/'. $this->race['_id'] .'/pic1.png'),
            'content' => $pageContent,
            'sheet_mod_class' => $pageNum % 2 == 0 ? 'sheet--odd' : 'sheet--even',
            'sheet_mod_bg_name' => $pageNum % 2 == 0 ? 'page1' : 'page2',
            'footer' => $this->tryRender(self::pathJoin(self::DIR_BLOCKS, self::DIR_LAYOUT, self::DIR_LAYOUT_BLOCKS, 'footer'), [
                'pokemon' => $this->race
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
}
