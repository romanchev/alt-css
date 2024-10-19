<?php if (!defined('MAKE_JAVASCRIPT')) die(); ?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title><?= $pageTitle ?><?= $addSeoText ?></title>
    <meta name="description" content="<?= $pageDescription ?><?= $addSeoText ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700;800;900&display=swap"
          rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.materialdesignicons.com/5.0.45/css/materialdesignicons.min.css">
    <link type="text/css" rel="stylesheet" href="/typo3conf/ext/ttmpl/Resources/Public/Tmpl/js/mmenu/mmenu.css">
    <link type="text/css" rel="stylesheet" href="/typo3conf/ext/ttmpl/Resources/Public/Simply/js/fancybox3/jquery.fancybox.min.css">
    <link type="text/css" rel="stylesheet" href="js/combo-tree/style.css">
    <link type="text/css" rel="stylesheet" href="style1.css?<?= $upload ?>">
</head>

<body class="body-<?= $platform ?>" id="sorting-<?= $view ?>">
<div id="header_top" class="fixed">
    <div class="container">
        <div class="row between">
            <a class="logo" href="/"></a>
            <div class="col">
                <?php include("$CACHEDIR/P-html-menu.php"); ?>
                <a id="btn_mobile" href="#"><span></span><span></span><span></span></a>
            </div>
        </div>
    </div>
</div>

<div class="wrapper">
    <div id="header">
        <div class="container">
            <h1 class="align-left" style="margin-top: 20px;"><?= $pageHeader ?></h1>
            <p>Последнее обновление:
                <a href="<?= $platform ?>.csv" title="Скачать таблицу в формате CSV"><b><?= $pubdate ?></b></a>,
                служба обеспечения совместимости
                <a href="mailto:gost@basealt.ru" title="Отправить письмо..."><b>&lt;gost@basealt.ru&gt;</b></a></p>
        </div>
    </div>
    <div id="main">
        <div id="section1">
            <div class="container">

                <div class="filter_holder">
                    <div class="view">
                        <div class="head">Сортировать:</div>
                        <div class="items">
                            <div class="item"><a class="<?= $categoryClass ?>" href="<?= $platform ?>-view2.html">по
                                    категориям</a></div>
                            <div class="item"><a class="<?= $vendorClass ?>" href="<?= $platform ?>-view1.html">по
                                    вендорам</a></div>
                            <div class="item"><a class="<?= $productClass ?>" href="<?= $platform ?>-view0.html">по
                                    продуктам</a></div>
                        </div>
                    </div>
                    <div class="buttons">
                        <div class="head">Платформы:</div>
                        <div class="items items-platf">
                            <div class="item"><a href="#all" class="active">Все</a></div>
                            <?= $platfButtons ?>
                        </div>
                    </div>
                </div>

                <form id="filter_form" class="no_fixed" action="#">
                    <input id="search_text" value="" placeholder="Поиск по названию компании или продукта"
                           style="width:380px">
                    <input name="platf" id="platf" type="hidden" value="Все">
                    <input name="categories" id="categories" type="text" placeholder="Все категории" autocomplete="off">
                    <input type="submit" value="Найти">
                    <a href="" id="filter-clean" class="filter-clean">Сбросить фильтр</a>
                    <span><a href="" id="filter-copy" class="filter-copy" title="Скопировать ссылку в буфер обмена"><img src="i/copy.png" alt="Копировать" title="Скопировать ссылку в буфер обмена"></a><span class="tooltip"></span></span>
                </form>
                <div class="more-info-block">
                <p class="more-info-wrapper">Дополнительная информация: &nbsp;  <a href="#" class="more-info-link" data-platform="<?= $platform ?>"></a></p> <!-- ↑ скрыть ↑ показать ↓ 🡣🠓 -->
                <div class="more-info">
                <?php include("$NTMPSDIR/P-html-text1-" . $platform . ".php"); ?>
                </div>
                </div>
            </div>
        </div>

        <div class="table_holder">
            <div id="no_results" style="display:none">
                <h2>По вашему запросу ничего не найдено.</h2>
            </div>
            <?= $content ?>
            <?= $notesHTML ?>
        </div>
    </div>
</div>
<a href="#" class="scrollToTop"></a>

<?php include("$CACHEDIR/P-html-menu-mobile.php"); ?>
<script src="/typo3conf/ext/ttmpl/Resources/Public/Tmpl/js/jquery-1.11.2.min.js"></script>
<script src="js/combo-tree/comboTreePlugin.js"></script>
<script src="/typo3conf/ext/ttmpl/Resources/Public/Tmpl/js/mmenu/mmenu.js"></script>
<script src="/typo3conf/ext/ttmpl/Resources/Public/Simply/js/fancybox3/jquery.fancybox.min.js"></script>
<script src="P-data-<?= $platform ?>.js?<?= $upload ?>"></script>
<script src="js/script.js?<?= $upload ?>"></script>

</body>
</html>