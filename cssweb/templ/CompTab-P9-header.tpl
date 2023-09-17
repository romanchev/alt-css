<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru" lang="ru">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Совместимость с дистрибутивами Альт на девятой платформе</title>
<meta name="robots" content="noindex, nofollow" />
<style type="text/css">
<!--

body { margin:0 0 700px 0; padding:0; color:#333; font-size:100%; font-style:normal; font-weight:normal; font-family:Arial; background-color:#fff; }
h1 { margin:0; font-size:230%; color:#eee; text-shadow: #000 2px 2px 0; }
h2 { margin:15px 5px 5px 0; padding-left:10px; color:#333; text-shadow: #cad5e2 1px 1px 0, #cad5e2 2px 2px 0; }
table.banner { width:100%; height:200px; color:#7e7e7e; background-color:#1f1f1f; box-shadow:3px 3px 6px rgba(0,0,0,0.2); }
table.buttons { background-color:#333; }
table.view { float:left; padding:0 5px 0 0; }
table.arch { width:99%; margin:0 10px; border:black; border-collapse:collapse; background-color:#eee; box-shadow:3px 3px 6px rgba(0,0,0,0.2); }
.banner a { color:#7e7e7e; text-decoration:none; width:100%; }
.banner a:hover { color:#eee; text-decoration:underline; }
.banner td { padding:5px; font-size:85%; }
.buttons td { padding:10px; text-align:center; font-weight:900; border:1px solid #7e7e7e; }
.buttons td:hover { background-color:#7e7e7e; border-color:#eee; }
.logocell { width:230px; }
.logo { padding: 15px 5px 5px 10px; }
.view td { width:90px; padding:2px; border: 1px solid #7e7e7e; }
.view td:hover { color:#000; background-color:#7e7e7e; border-color:#eee; }
.view a { color:#e7e7e7; }
.arch tr:hover, .arch td.group:hover { background-color: #ff9; }
.arch a { text-decoration:none; }
.arch a:link, .arch a:visited { color:#33e; }
.arch a:hover { color:#00f; text-decoration:underline; }
.arch th { padding:10px 5px; font-size:110%; background:#ccc; border: 1px solid black; }
th.product { text-align:left; vertical-align:middle; }
th.help { text-align:center; vertical-align:middle; }
th.cell { width:14%; text-align:center; vertical-align:middle; }
th.small { width:14%; text-align:center; vertical-align:middle; font-style:normal; font-weight:normal; }
.arch td { border: 1px solid black; }
td.product { padding: 5px; text-align:left; }
td.category { padding: 5px; text-align:left; }
td.help { padding: 5px; text-align:center; }
td.compat { padding: 15px 5px 15px; text-align:center; background:#90ff90; vertical-align:middle; }
td.compat:hover { background-color: #3f3; }
td.group { padding:5px; color:brown; font-weight:900; font-size:110%; text-align:left; background-color:#e0e0f7; }
.notes { margin:20px; padding:20px 60px; font-size:120%; font-style:italic; background:#eee; color:#000; border:1px solid #000; border-radius:5px; box-shadow:3px 3px 6px rgba(0,0,0,0.2); }
.warn  { margin:20px; padding:20px 60px; font-size:120%; font-style:italic; background:#fcc; color:#000; border:1px solid #000; border-radius:5px; box-shadow:3px 3px 6px rgba(0,0,0,0.2); }

-->
</style>
</head>
<body>
<table class="banner">
<tbody><tr>
<td class="logocell"><a href="https://www.basealt.ru/" title="Вернуться на главную страницу"><img src="icons/basealt-logo.png" alt="BaseALT" class="logo" /></a></td>
<td><h1>Совместимость с дистрибутивами Альт</h1>
Последнее обновление: <a href="{UPLOAD}" title="Скачать в формате CSV"><b>{DATE}</b></a>, служба обеспечения совместимости
<a href="mailto:gost@basealt.ru" title="Отправить письмо..."><b>&lt;gost@basealt.ru&gt;</b></a><br/><br/>
<table class="view">
<tr><td>Сортировать:</td></tr>
<tr><td><a href="{BYGRPPAGE}" class="view">по категориям</a></td></tr>
<tr><td><a href="{BYVNDPAGE}" class="view">по вендорам</a></td></tr>
<tr><td><a href="{BYPRDPAGE}" class="view">по продуктам</a></td></tr>
</table>{ARCHBUTTONS}</td>
<td>&nbsp;</td>
</tr></tbody>
</table>

<h2>Совместимость с ПО на следующих дистрибутивах идентична:</h2>
<div>
<ol>
<li>«<b>Альт Рабочая станция К 9</b>» (x86_64) — см. колонку «<b>Альт Рабочая станция 9</b>».</li>
<li>«<b>Альт Сервер виртуализации 9</b>» (x86_64, aarch64 и ppc64le) — см. колонку «<b>Альт Сервер 9</b>».</li>
</ol>

<p class="warn">В таблице представлена информация о совместимости с устаревшей
    версией «<b>Альт 8 СП</b>» после инспекционного контроля в декабре 2021. Информацию
    о совместимости с актуальным выпуском «<b>Альт СП релиз 10</b>» можно посмотреть
    в <a href="P10-view2.html" title="Альт СП релиз 10">другой таблице</a>.</p>

<p class="notes">По вопросам размещения в таблице ваших вариантов инструкций для
   ОС Альт или ссылок на них, вопросам совместимости, найденных опечаток, обращайтесь в
   <a href="mailto:gost@basealt.ru" title="Отправить запрос">службу обеспечения совместимости</a>.
   Для получения доступа к демонстрационному стенду с интересующим вас ПО, получения дистрибутивов
   Альт СП и инструкций по установке перечисленных программ для Альт СП, обращайтесь в
   <a href="mailto:sales@basealt.ru" title="Отправить запрос">отдел продаж</a>.</p>
</div>

