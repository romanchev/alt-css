<?php

if (!defined('MAKE_JAVASCRIPT'))
    die();
$jscond = ($group_by == MAKE_JAVASCRIPT);

switch ($platform) {
    case 'P11':
	$categoryColumn = 12; // начиная с нуля
    $pageTitle = 'Совместимость с дистрибутивами Альт на одиннадцатой платформе';
    $pageDescription = 'Совместимость с дистрибутивами Альт на одиннадцатой платформе для x86_64, aarch64, e2kv4, e2kv5, e2kv6'; // e2kv4,e2kv5,e2kv6
	$pageHeader = 'Совместимость с дистрибутивами Альт';
	break;
    case 'P10':
	$categoryColumn = 12; // начиная с нуля
	$pageTitle  = 'Совместимость с дистрибутивами Альт на десятой платформе';
	$pageDescription = 'Совместимость с дистрибутивами Альт на десятой платформе для x86_64, i586, aarch64, e2k, e2kv4, e2kv5, ppc64le';
	$pageHeader = 'Совместимость с дистрибутивами Альт';
	break;
    case 'P9':
	$categoryColumn = 12; // начиная с нуля
	$pageTitle  = 'Совместимость с дистрибутивами Альт на девятой платформе';
	$pageDescription = 'Совместимость с дистрибутивами Альт на девятой платформе для x86_64, i586, aarch64, armh, e2k, e2kv4, mipsel, ppc64le';
	$pageHeader = 'Совместимость с дистрибутивами Альт';
	break;
    case '8SP':
	$categoryColumn = 9;  // начиная с нуля
	$pageTitle  = 'Совместимость с устаревшими дистрибутивами Альт 8 СП';
	$pageDescription = 'Совместимость с устаревшими дистрибутивами Альт 8 СП для x86_64, i586, e2k, e2kv4';
	$pageHeader = 'Совместимость с устаревшими дистрибутивами Альт 8 СП';
	break;
    case 'S10':
	$categoryColumn = 8;  // начиная с нуля
	$pageTitle  = 'Совместимость с дистрибутивом Simply Linux 10';
	$pageDescription = 'Совместимость с дистрибутивом Simply Linux 10 для x86_64, i586, aarch64';
	$pageHeader = 'Совместимость с дистрибутивом Simply Linux 10';
	break;
}

switch ($group_by) {
    case GROUP_BY_PRODUCTS:
	$productClass = 'active';
	$view = 'product';
	$addSeoText = ' - сортировка по продуктам';
	break;
    case GROUP_BY_VENDORS:
	$vendorClass = 'active';
	$view = 'vendor';
	$addSeoText = ' - сортировка по вендорам';
	break;
    case GROUP_BY_GROUPS:
	$categoryClass = 'active';
	$view = 'category';
	$addSeoText = ' - сортировка по категориям';
	break;
}


$row = 1;
$products = [];
if (($handle = fopen($input_file, "r")) === false)
    fatal("Couldn't read input CSV-data!");
while (($data = fgetcsv($handle, 5000, "|")) !== false) {
    $num = count($data);
    $products[$row] = $data;
    $row++;
}
fclose($handle);
unset($input_file);
//print_r($products);
//die();

$cats = [];
$platf = [];
$vends = [];
$vendsURL = [];
$notes = [];
foreach ($products as $k => $product) {
    $cats[] = $product[$categoryColumn];
    $platf[] = $product[0];
    $vends[] = $product[1];
    if ($product[2]) {
        $vendsURL[$product[1]] = $product[2];
    }
}
$platf = array_unique($platf);
$platfButtons = '';
foreach ($platf as $p) {
    $platfButtons .= '<div class="item"><a href="#">' . $p . '</a></div>';
}

$cats = array_unique($cats);
$cats1 = [];
foreach ($cats as $k => $cat) {
    if (strpos($cat, 'Софт ::') === 0) {
        $cats1[] = $cat;
    } else {
        $cats2[] = $cat;
    }
}
sort($cats1);
sort($cats2);
$categoriesNum = [];
$categories = [];
$i = 200;
foreach ($cats1 as $cat) {
    $categoriesNum[$cat] = $i;
    $categories[$i] = $cat;
    $i++;
}
foreach ($cats2 as $cat) {
    $categoriesNum[$cat] = $i;
    $categories[$i] = $cat;
    $i++;
}
//print_r($categories);
// die();

$vends = array_unique($vends);
$vendorsForSorting = [];
foreach ($vends as $vendor) {
    $vendorKey = titleForSorting($vendor);
    $vendorsForSorting[$vendorKey] = $vendor;
}
ksort($vendorsForSorting);
//print_r($vendorsForSorting);
//die();

$vendorsNum = [];
$i = 1;
foreach ($vendorsForSorting as $key => $v) {
    $vendorsNum[$v] = $i;
    $vendors[$i] = $v;
    $vendorsSortName[$v] = $key;
    $i++;
}
//print_r($vendors);
//die();

if ($jscond) {
    echo "var categoriesNums = ['";
    echo implode("','", $categoriesNum);
    echo "'];\n";
}

$categoriesTree = [];
foreach ($categoriesNum as $cat => $i) {
    $parts = explode(' :: ', $cat);
    if (isset($parts[2]) && $parts[2]) {
        $categoriesTree[$parts[0]][$parts[1]][$parts[2]] = [
            $i
        ];
    } elseif (isset($parts[1]) && $parts[1]) {
        $categoriesTree[$parts[0]][$parts[1]] = [
            $i
        ];
    }
}
//print_r($categoriesTree);
//die();

if ($jscond) {
    $n = 1;
    echo 'var categoriesData = [';

    foreach ($categoriesTree as $key => $arr) {
        echo '{';
        echo 'id: ' . $n . ',';
        echo 'title: \'' . $key . '\',';
        echo 'subs: [';

        foreach ($arr as $key2 => $arr2) {
            echo '{';
            if (isset($arr2[0]) && $arr2[0]) {
                echo 'id: ' . $arr2[0] . ',';
                echo 'title: \'' . $key2 . '\',';
            } else {
                $n++;
                echo 'id: ' . $n . ',';
                echo 'title: \'' . $key2 . '\',';
                echo 'subs: [';
                foreach ($arr2 as $key3 => $arr3) {
                    echo '{';

                    if (isset($arr3[0]) && $arr3[0]) {
                        echo 'id: ' . $arr3[0] . ',';
                        echo 'title: \'' . $key3 . '\',';
                    } else {
                        $n++;
                        echo 'id: ' . $n . ',';
                        echo 'title: \'' . $key3 . '\',';
                        echo 'subs: [';
                        foreach ($arr3 as $key4 => $arr4) {
                            echo '{';
                            if (isset($arr4[0]) && $arr4[0]) {
                                echo 'id: ' . $arr4[0] . ',';
                                echo 'title: \'' . $key4 . '\',';
                            }
                            echo '},';
                        }
                        echo ']';
                    }
                    echo '},';
                }
                echo ']';
            }
            echo '},';
        }
        echo ']';
        echo '},';
        $n++;
    }

    echo '];';
}

//print_r($categories);
//die();

$arch = []; // архитектуры
$productsCats = [];
foreach ($products as $k => $product) {
    $product[100] = $vendorsSortName[$product[1]]; // ставим название вендора для сортировки
    $product[101] = titleForSorting($product[3]); // название продукта для сортировки

    $arch[$product[0]][$k] = $product;
    $productsCats[$k] = (string)$categoriesNum[$product[$categoryColumn]];
}

$archCats = [];
$archCatsForSearch = [];
foreach ($arch as $a => $productsArch) {
    foreach ($productsArch as $n => $product) {
        $archCats [$a] [$categoriesNum[$product[$categoryColumn]]] [$n] = $product;
        $archCatsForSearch[$a] [$categoriesNum[$product[$categoryColumn]]] [$n] = mb_strtolower($product[1] . ' ' . $product[2] . ' ' . $product[3],
            'UTF-8');
    }
}
foreach ($archCats as $a => $productsArch) {
    ksort($archCats[$a], SORT_NUMERIC);
    ksort($archCatsForSearch[$a], SORT_NUMERIC);
}

foreach ($archCats as $a => $productsArch) {
    foreach ($archCats[$a] as $cat => $productsOfCat) {
        uasort($archCats[$a][$cat], 'compareProducts');
//        print_r($archCats[$a][$cat]);
    }
}

if ($jscond) {
    echo "\nvar productsData = ";
    echo json_encode($archCatsForSearch, JSON_UNESCAPED_UNICODE); // ."JSON.parse()"
}

$archVends = [];
$archVendsForSearch = [];
foreach ($arch as $a => $productsArch) {
    foreach ($productsArch as $n => $product) {
        $archVends [$a] [$vendorsNum[$product[1]]] [$n] = $product;
        $archVendsForSearch[$a] [$vendorsNum[$product[1]]] [$n] = mb_strtolower($product[1] . ' ' . $product[2] . ' ' . $product[3],
            'UTF-8');
    }
}
foreach ($archVends as $a => $productsArch) {
    ksort($archVends[$a], SORT_NUMERIC);
    ksort($archVendsForSearch[$a], SORT_NUMERIC);
}

foreach ($archVends as $a => $productsArch) {
    foreach ($archVends[$a] as $vend => $productsOfVend) {
        uasort($archVends[$a][$vend], 'compareProductsInVend');
    }
}

if ($jscond) {
    echo "\nvar productsDataVends = ";
    echo json_encode($archVendsForSearch, JSON_UNESCAPED_UNICODE); // ."JSON.parse()"
    echo "\nvar productsDataCats = ";
    echo json_encode($productsCats, JSON_UNESCAPED_UNICODE); // ."JSON.parse()"
}

$archVendProds = [];
$archVendProdsForSearch = [];
foreach ($arch as $a => $productsArch) {
    $currentVendor = '';
    $currentVendorCounter = 0;
    foreach ($productsArch as $n => $product) {
        if ($currentVendor != $product[1]) {
            $currentVendorCounter++;
            $currentVendor = $product[1];
        }
        $archVendProds [$a] [$currentVendorCounter] [$n] = $product;
        $archVendProdsForSearch[$a] [$currentVendorCounter] [$n] = mb_strtolower($product[1] . ' ' . $product[2] . ' ' . $product[3],
            'UTF-8');
    }
}

foreach ($archVendProds as $a => $productsArch) {
    foreach ($archVendProds[$a] as $vend => $productsOfVend) {
        uasort($archVendProds[$a][$vend], 'compareProductsInVend');
    }
}

if ($jscond) {
    echo "\nvar productsDataVendProds = ";
    echo json_encode($archVendProdsForSearch, JSON_UNESCAPED_UNICODE); // ."JSON.parse()"
    echo "\n";
    exit;
}
unset($jscond);

if ($group_by == GROUP_BY_GROUPS) {
//    print_r($archCats); die();
    $content = makeTabel($archCats);
} elseif ($group_by == GROUP_BY_VENDORS) {
    $content = makeTabel($archVends);
} elseif ($group_by == GROUP_BY_PRODUCTS) {
    $content = makeTabel($archVendProds);
}

// Примечания
$notesHTML = '';
if (count($notes) > 0) {
    $notesHTML = '<div id="notes"><h2 class="notes-title">Примечания</h2>';
    $i = 1;
    foreach ($notes as $note) {
        $notesHTML .= '<p id="note' . $i . '" class="note-item"><span>' . $i . '</span>. ' . $note . '</p>';
        $i++;
    }
    $notesHTML .= '</div>';
}

/**********************************************************************************************************************/


function titleForSorting($title) {
    preg_match("/«(.+)»/i",$title, $matches);
    if (isset($matches[1]) && $matches[1]) {
        $result = trim($matches[1]);
    } else {
        $result = $title;
    }
    return $result;
}

function isVersionNumeric($x) {
    return preg_match("/^[0-9.]+$/i", $x);
}

function compareProducts($x1, $x2) {
    if ($x1[100] == $x2[100]) { // имя вендора, обрезанное для сортировки
        if ($x1[3] == $x2[3]) { // названия продуктов совпадают
            // если версии числовые обе, то переворачиваем наоборт
            if (isVersionNumeric($x1[5]) && isVersionNumeric($x2[5])) {
//                $result = -strcmp($x1[5], $x2[5]);
                $result = version_compare($x2[5], $x1[5]);  //

            } else {
                $result = strcmp( mb_strtolower($x1[5],'UTF-8'), mb_strtolower($x2[5],'UTF-8'));
            }
        } else {
            $result = strcmp( mb_strtolower($x1[101],'UTF-8'), mb_strtolower($x2[101],'UTF-8'));
        }
    } else {
        $result = strcmp( mb_strtolower($x1[100],'UTF-8'), mb_strtolower($x2[100],'UTF-8'));
    }
    return $result;
}

function compareProductsInVend($x1, $x2) {
    if ($x1[3] == $x2[3]) { // названия продуктов совпадают
        // если версии числовые обе, то переворачиваем наоборт
        if (isVersionNumeric($x1[5]) && isVersionNumeric($x2[5])) {
            $result = version_compare($x2[5], $x1[5]);  //
        } else {
            $result = strcmp( mb_strtolower($x1[5],'UTF-8'), mb_strtolower($x2[5],'UTF-8'));
        }
    } else {
        $result = $result = strcmp( mb_strtolower($x1[101],'UTF-8'), mb_strtolower($x2[101],'UTF-8'));;
    }
    return $result;
}

function makeTabelHeaders($a, $platform, $view): string
{
    global $fcol;

    $help = '<a href="https://www.altlinux.org/" target="_blank" title="Инструкции по установке для дистрибутивов Альт на бранче ' .
	    htmlspecialchars($platform) .
	    ' (в стадии наполнения). Если не указано, поищите на нашей ВиКи или запросите в отделе продаж.">HELP</a>';

    if ($platform == 'P11') {
        $result = '
            <tr>
                <th rowspan="2" class="product">' . (($view == 'category') ? 'Категория, продукт, производитель' : 'Производитель, продукт') . '</th>
                <th rowspan="2" class="help">' . $help . '</th>
                <th colspan="2" class="cell">' . (($a != 'x86_64' && $a != 'aarch64') ? '&nbsp;' : 'Альт&nbsp;СП&nbsp;релиз&nbsp;11') . '</th>
                <th rowspan="2" class="cell">' . (($a == 'ppc64le') ? '&nbsp;' : 'Альт Рабочая станция 11') . '</th>
                <th rowspan="2" class="cell">' . (($a == 'ppc64le') ? '&nbsp;' : 'Альт Образование 11') . '</th>
                <th rowspan="2" class="cell">Альт Сервер 10</th>
            <tr><th class="small">' . (($a != 'x86_64' && $a != 'aarch64') ? '&nbsp;' : '(рабочая&nbsp;станция)') . '</th><th class="small">' . (($a != 'x86_64' && $a != 'aarch64') ? '&nbsp;' : '(сервер)') . '</th></tr>
            </tr>
        ';
    } elseif ($platform == 'P10') {
        $result = '
            <tr>
                <th rowspan="2" class="product">' . (($view == 'category') ? 'Категория, продукт, производитель' : 'Производитель, продукт') . '</th>
                <th rowspan="2" class="help">' . $help . '</th>
                <th colspan="2" class="cell">' . (($a != 'x86_64' && $a != 'aarch64') ? '&nbsp;' : 'Альт&nbsp;СП&nbsp;релиз&nbsp;10') . '</th>
                <th rowspan="2" class="cell">' . (($a == 'ppc64le') ? '&nbsp;' : 'Альт Рабочая станция 10') . '</th>
                <th rowspan="2" class="cell">' . (($a == 'ppc64le') ? '&nbsp;' : 'Альт Образование 10') . '</th>
                <th rowspan="2" class="cell">Альт Сервер 10</th>
            <tr><th class="small">' . (($a != 'x86_64' && $a != 'aarch64') ? '&nbsp;' : '(рабочая&nbsp;станция)') . '</th><th class="small">' . (($a != 'x86_64' && $a != 'aarch64') ? '&nbsp;' : '(сервер)') . '</th></tr>
            </tr>
        ';
    } elseif ($platform == 'P9') {
        $result = '
            <tr>
                <th rowspan="2" class="product">' . $fcol . '</th>
                <th rowspan="2" class="help">' . $help . '</th>
                <th colspan="2" class="cell">' . (($a == 'mipsel') ? '&nbsp;' : 'Альт&nbsp;8&nbsp;СП') . '</th>
                <th rowspan="2" class="cell">' . (($a == 'ppc64le') ? '&nbsp;' : 'Альт&nbsp;Рабочая&nbsp;станция&nbsp;9') . '</th>
                <th rowspan="2" class="cell">' . (($a == 'armh' || $a == 'mipsel' || $a == 'ppc64le') ? '&nbsp;' : 'Альт&nbsp;Образование&nbsp;9') . '</th>
                <th rowspan="2" class="cell">' . (($a == 'i586' || $a == 'armh' || $a == 'mipsel') ? '&nbsp;' : 'Альт Сервер 9') . '</th>
            </tr>
            <tr><th class="small">' . (($a == 'mipsel' || $a == 'ppc64le') ? '&nbsp;' : '(рабочая&nbsp;станция)') . '</th><th class="small">' . (($a == 'mipsel') ? '&nbsp;' : '(сервер)') . '</th></tr>
        ';
    } elseif ($platform == '8SP') {
        $result = '
            <tr>
                <th rowspan="2" class="product">' . $fcol . '</th>
                <th rowspan="2" class="help">' . $help . '</th>
                <th colspan="2" class="cell">Альт 8 СП (ИК: март 2020)</th>
                <th rowspan="2" class="cell"></th>
            </tr>
            <tr><th class="small">(рабочая&nbsp;станция)</th><th class="small">(сервер)</th></tr>
        ';

    } elseif ($platform == 'S10') {
        $result = '
            <tr>
                <th class="product">' . $fcol . '</th>
                <th class="help">' . $help . '</th>
                <th class="cell">Simply Linux 10</th>
                <th class="empty">&nbsp;</th>
            </tr>
        ';
    } else {
        $result = '';
    }

    return $result;
}

function makeTabelCells($product, $platform): string
{
    if ($platform == 'P11') {
        $result = '
                ' . makeSert($product[7]) . '
                ' . makeSert($product[8]) . '
                ' . makeSert($product[9]) . '
                ' . makeSert($product[10]) . '
                ' . makeSert($product[11]) . '
        ';
    } elseif ($platform == 'P10') {
        $result = '
                ' . makeSert($product[7]) . '
                ' . makeSert($product[8]) . '
                ' . makeSert($product[9]) . '
                ' . makeSert($product[10]) . '
                ' . makeSert($product[11]) . '
        ';
    } elseif ($platform == 'P9') {
        $result = '
                ' . makeSert($product[7]) . '
                ' . makeSert($product[8]) . '
                ' . makeSert($product[9]) . '
                ' . makeSert($product[10]) . '
                ' . makeSert($product[11]) . '
        ';
    } elseif ($platform == '8SP') {
        $result = '
                ' . makeSert($product[7]) . '
                ' . makeSert($product[8]) . '
                <td class="empty">&nbsp;</td>
        ';

    } elseif ($platform == 'S10') {
        $result = '
                ' . makeSert($product[7]) . '
                <td class="empty">&nbsp;</td>
        ';
    } else {
        $result = '';
    }

    return $result;
}

function makeTabelColspan($platform): string
{
    if ($platform == 'P11') {
        $result = 7;
    } elseif ($platform == 'P10') {
        $result = 7;
    } elseif ($platform == 'P9') {
        $result = 7;
    } elseif ($platform == '8SP') {
        $result = 5;
    } elseif ($platform == 'S10') {
        $result = 4;
    } else {
        $result = '';
    }

    return $result;
}


function makeTabel($archCats): string
{
    global $view;
    global $group_by;
    global $hw_platforms;
    global $categories;
    global $vendsURL;
    global $vendors;
    global $platform;

//print_r($archCats);
    $content = '';
    foreach ($archCats as $a => $productsCatArch) {
        $content .= '
        <a id="' . $a . '" ></a>
        <h3 class="platf platf_' . $a . '">Совместимость с дистрибутивами на архитектуре ' .
    	    htmlspecialchars($a . ' (' . $hw_platforms[$a] . ')') . '</h3>
        <table class="arch contenttable platf platf_' . $a . '">
            <thead>
                ' . makeTabelHeaders($a, $platform, $view) . '
            </thead>
            <tbody>
';

        foreach ($productsCatArch as $catId => $productCat) {
            //print_r($productCat); die();
            if ($group_by == GROUP_BY_GROUPS) {
                $rowClassPrefix = 'cat';
                $title = $categories[$catId];
            } elseif ($group_by == GROUP_BY_VENDORS) {
                $rowClassPrefix = 'vend';
                if ($vendsURL[$vendors[$catId]]) {
                    $title = '<a href="' . $vendsURL[$vendors[$catId]] . '" target="_blank" rel="nofollow" class="vendor-title" title="Открыть сайт в новом окне">' . $vendors[$catId] . '</a>';
                } else {
                    $title = $vendors[$catId];
                }
            } elseif ($group_by == GROUP_BY_PRODUCTS) {
                $rowClassPrefix = 'vend';
                $vendorTitle = $productCat[array_key_first($productCat)][1];
                if ($vendsURL[$vendorTitle]) {
                    $title = '<a href="' . $vendsURL[$vendorTitle] . '" target="_blank" rel="nofollow" class="vendor-title" title="Открыть сайт в новом окне">' . $vendorTitle . '</a>';
                } else {
                    $title = $vendorTitle;
                }
            }

            $content .= '
            <tr class="cat-rows ' . $rowClassPrefix . $catId . '" id="' . $a . '_' . $catId . '">
                <td colspan="' . makeTabelColspan($platform) . '" class="group">' . $title . '</td>
            </tr>
        ';

            foreach ($productCat as $n => $product) {
                if ($group_by == GROUP_BY_GROUPS) {
                    $linkToVendor = '<br><a href="' . $product[2] . '" target="_blank" rel="nofollow" title="Открыть сайт в новом окне">' . $product[1] . '</a>';
                } else {
                    $linkToVendor = '';
                }
                $content .= '
            <tr class="cat-rows cat' . $catId . '" id="pr' . $n . '">
                <td class="product">
                 ' . makeTitle($product[3], $product[4]) . ' ' . makeVersion($product[5]) . $linkToVendor . '
                </td>
                <td class="help">
                ' . makeHelpLink($product[6]) . '
                </td>
                ' . makeTabelCells($product, $platform) . '
            </tr>
';
            }
        }
        $content .= '
            </tbody>
        </table>
    ';
    }
    return $content;
}

function makeNote($title): array
{
    global $notes;
    preg_match('/(.*)\[\[(.+?)\]\]/i', $title, $matches);
    if (isset($matches[2]) && $matches[2]) {
        $existingNoteNum = array_search($matches[2], $notes);
        if ($existingNoteNum) {
            $noteNum = $existingNoteNum;
        } else {
            $noteNum = count($notes) + 1;
            $notes[$noteNum] = $matches[2];
        }
        $titleClean = $matches[1];
        $noteLink = '<sup class="note-link" data-note="' . $noteNum . '"><a href="#notes" title="' . $matches[2] . '"><b><span>' . $noteNum . '</span>)</b></a></sup>';
    } else {
        $titleClean = $title;
        $noteLink = '';
    }
    return [$titleClean, $noteLink];
}


function makeTitle($title, $url): string
{
    $makeNote = makeNote($title);
    if ($url) {
        $result = '<a href="' . $url . '" target="_blank" rel="nofollow" title="Открыть описание в новом окне"><b>' . $makeNote[0] . '</b></a>' . $makeNote[1];
    } else {
        $result = '<b>' . $makeNote[0] . '</b>' . $makeNote[1];
    }
    return $result;
}

function makeVersion($version): string
{
    $version = trim($version);
    if ($version) {
        if (isVersionNumeric($version)) {
            $result = $version;
        } else {
            $result = '(' . $version . ')';
        }
    } else {
        $result = '';
    }
    return $result;
}

function makeSert($s): string
{
    // нужно обрабатывать ссылки 1) 2) 3)
    $makeNote = makeNote($s);
    $s = $makeNote[0];
    if (($s == 'Совместимы') || ($s == '#') || ($s == '+')) {
        $result = '<td class="compat compart">Совместимы' . $makeNote[1] . '</td>';
    } elseif ($s) {
        $result = '<td class="compat cert"><a class="fancybox" href="certs/' . $s . '.jpg" title="Открыть">Сертификат</a>' . $makeNote[1] . '</td>';
    } else {
        $result = '<td class="empty">&nbsp;</td>';
    }
    return $result;
}

function makeHelpLink($s): string
{
    if (strpos($s, 'http') !== false) {
        $result = '<a href="' . $s . '" target="_blank" rel="nofollow" title="Открыть инструкцию в новом окне"><img src="i/w.svg" alt="www" width="23px"></a>';
    } elseif ($s == '') {
        $result = '<div></div>';
    } else {
        $result = '<a href="instr/' . $s . '.pdf" class="fancybox"  title="Открыть инструкцию в новом окне"><img src="i/p.svg" alt="PDF" width="20px"></a>';
        // target="_blank" rel="nofollow"
    }
    return $result;
}

?>