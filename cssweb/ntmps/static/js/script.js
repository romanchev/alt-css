$(document).ready(function ($) {

    //new menu
    var mobile_menu = $("#mobile_menu");
    mobile_menu.mmenu(
        {
            "extensions": [
                "position-front",
                "position-right"
            ],
            "navbars": [
                {
                    "position": "top",
                    "content": ["prev", "title", "close"],
                },
            ],
        },
        {
            language: "ru"
        },
    );

    $('#main_menu > li > a:not(#mm550)').on('click', function (event) {
        event.preventDefault();
        var ths = $(this).parent('li');
        var sub = ths.children('.sub');
        if (sub.is(':hidden')) {
            ths.closest('#main_menu').children('li.open').removeClass('open').children('.sub:visible').slideUp(400);
            ths.addClass('open');
            sub.slideDown(400);
        } else {
            ths.removeClass('open');
            sub.slideUp(400);
        }
    });
    $('#btn_mobile').on('click', function (event) {
        event.preventDefault();
        if (mobile_menu.hasClass('mm-menu_opened')) {
            mobile_menu.data("mmenu").close();
        } else {
            mobile_menu.data("mmenu").open();
        }
    });

    $(document).mouseup(function (e) {
        var div = $('#main_menu');
        if (!div.is(e.target) && div.has(e.target).length === 0) {
            div.children('li.open').removeClass('open').children('.sub').slideUp(400);
        }
    });


    //Check to see if the window is top if not then display button
    $(window).scroll(function(){
        if ($(this).scrollTop() > 100) {
            $('.scrollToTop').fadeIn();
        } else {
            $('.scrollToTop').fadeOut();
        }
    });
    //Click event to scroll to top
    $('.scrollToTop').click(function(){
        $('html, body').animate({scrollTop : 0},800);
        return false;
    });

    $(".fancybox").fancybox();
    $(".fancybox-pdf").fancybox({
//        width  : 600,
//        height : 300,
        type   :'iframe'
    });



    let isInfoVisible = localStorage.getItem('isInfoVisible-'+$(".more-info-link").data('platform'));
    if (isInfoVisible !== '0') {
        $(".more-info-link").text("скрыть ↑");
        $(".more-info").show();
    } else {
        $(".more-info-link").text("показать ↓");
    }
    $(".more-info-link").on('click', function (e) {
        e.preventDefault();

        if ($(".more-info").is(":visible")) {
            $(".more-info").hide('fast');
            $(this).text("показать ↓");
            localStorage.setItem('isInfoVisible-'+$(this).data('platform'), '0');
        } else {
            $(".more-info").show('fast');
            $(this).text("скрыть ↑");
            localStorage.setItem('isInfoVisible-'+$(this).data('platform'), '1');
        }
    });
    var categoriesTree = $('#categories').comboTree({
        source: categoriesData,
        isMultiple: true,
        cascadeSelect: true,
        collapse: false
    });

    $("#sorting-category #filter_form").on('submit', function (e) {
        e.preventDefault();
        document.body.style.cursor = 'progress';
        // если есть непустой фальтр
        let searchText = $('#search_text').val().toLowerCase();
        let platfSelected = $('#platf').val();
        let catIds = categoriesTree.getSelectedIds();
        if (catIds) {
            catIds = catIds.map(String);
        }
        $('.platf').show();
        $('.cat-rows').show();
        $('#no_results').hide();
        let noResults = true;
        for (platf in productsData) {
            let platfWillBeHidden = true;
            if (platfSelected === 'Все' || platfSelected === platf) {
                // если правформа не выбрана вообще, или выбрана текущая, то обрабатываем платформу
                for (catId in productsData[platf]){
                    let catWillBeHidden = true;
                    if (catIds === null || catIds.includes(catId)) {
                        // если категории не выбраны вообще, или текущая среди выбранных, то обрабатываем категорию
//                            platfWillBeHidden = false;
                        // если ищем по названию
                        if (searchText !== '') {
                            for (prodId in productsData[platf][catId]){
                                if (productsData[platf][catId][prodId].indexOf(searchText) !== -1) {
                                    // нашли
                                    platfWillBeHidden = false;
                                    catWillBeHidden = false;
                                } else {
                                    // скрываем товар
                                    $('#pr'+prodId).hide();
                                }
                            }
                        } else {
                            platfWillBeHidden = false;
                            catWillBeHidden = false;
                        }
                    } else {
                        // если категории. выбраны, то категории, которые не выбраны, будут скрыты (для текущий платформы)
                    }
                    if (catWillBeHidden) {
                        $('#'+platf+'_'+catId).hide();
                    }
                }
            } else {
                // если платф. выбрана, то платформы, которые не выбраны, будут скрыты
            }
            if (platfWillBeHidden) {
                $('.platf_'+platf).hide();
            } else {
                noResults = false;
            }
        }

        if (catIds !== null) {
               categoriesNums.forEach(function (catId) {
                 if (catIds.includes(catId)) {
                 } else {
                     $('.cat' + catId).hide();
                 }
             });
        }
        if (noResults) {
            $('#no_results').show();
        }
        updateNotes();
        document.body.style.cursor = 'default';
    });


    $("#sorting-vendor #filter_form").on('submit', function (e) {
        e.preventDefault();
        document.body.style.cursor = 'progress';
        // если есть непустой фальтр
        let searchText = $('#search_text').val().toLowerCase();
        let platfSelected = $('#platf').val();
        let catIds = categoriesTree.getSelectedIds();
        if (catIds) {
            catIds = catIds.map(String);
        }
        $('.platf').show();
        $('.cat-rows').show();
        $('#no_results').hide();
        let noResults = true;
        for (platf in productsDataVends) {
            let platfWillBeHidden = true;
            if (platfSelected === 'Все' || platfSelected === platf) {
                // если пратформа не выбрана вообще, или выбрана текущая, то обрабатываем платформу
                for (vendId in productsDataVends[platf]){
                    let vendWillBeHidden = true;
//                            platfWillBeHidden = false;
                    // если ищем по названию
                    if (searchText !== '' || catIds !== null) {
                        for (prodId in productsDataVends[platf][vendId]){
                            let productWillBeHidden = true;
                            if ( searchText !== '' && catIds === null) {
                                if (productsDataVends[platf][vendId][prodId].indexOf(searchText) !== -1) {
                                    productWillBeHidden = false;
                                }
                            } else if (searchText === '' && catIds !== null) {
                                if (catIds.includes(productsDataCats[prodId])){
                                    productWillBeHidden = false;
                                }
                            } else if (searchText !== '' && catIds !== null) {
                                if (productsDataVends[platf][vendId][prodId].indexOf(searchText) !== -1 && catIds.includes(productsDataCats[prodId])) {
                                    productWillBeHidden = false;
                                }
                            }
                            if (productWillBeHidden) {
                                // скрываем товар
                                $('#pr'+prodId).hide();
                            } else {
                                // нашли
                                platfWillBeHidden = false;
                                vendWillBeHidden = false;
                            }
                        }
                    } else {
                        platfWillBeHidden = false;
                        vendWillBeHidden = false;
                    }

                    if (vendWillBeHidden) {
                        $('#'+platf+'_'+vendId).hide();
                    }
                }
            } else {
                // если платф. выбрана, то платформы, которые не выбраны, будут скрыты
            }
            if (platfWillBeHidden) {
                $('.platf_'+platf).hide();
            } else {
                noResults = false;
            }
        }
        if (noResults) {
            $('#no_results').show();
        }
        updateNotes();
        document.body.style.cursor = 'default';
    });

    $("#sorting-product #filter_form").on('submit', function (e) {
        e.preventDefault();
        document.body.style.cursor = 'progress';
        // если есть непустой фальтр
        let searchText = $('#search_text').val().toLowerCase();
        let platfSelected = $('#platf').val();
        let catIds = categoriesTree.getSelectedIds();
        if (catIds) {
            catIds = catIds.map(String);
        }
        $('.platf').show();
        $('.cat-rows').show();
        $('#no_results').hide();
        let noResults = true;
        for (platf in productsDataVendProds) {
            let platfWillBeHidden = true;
            if (platfSelected === 'Все' || platfSelected === platf) {
                // если пратформа не выбрана вообще, или выбрана текущая, то обрабатываем платформу
                for (vendId in productsDataVendProds[platf]){
                    let vendWillBeHidden = true;
//                            platfWillBeHidden = false;
                    // если ищем по названию
                    if (searchText !== '' || catIds !== null) {
                        for (prodId in productsDataVendProds[platf][vendId]){
                            let productWillBeHidden = true;
                            if ( searchText !== '' && catIds === null) {
                                if (productsDataVendProds[platf][vendId][prodId].indexOf(searchText) !== -1) {
                                    productWillBeHidden = false;
                                }
                            } else if (searchText === '' && catIds !== null) {
                                if (catIds.includes(productsDataCats[prodId])){
                                    productWillBeHidden = false;
                                }
                            } else if (searchText !== '' && catIds !== null) {
                                if (productsDataVendProds[platf][vendId][prodId].indexOf(searchText) !== -1 && catIds.includes(productsDataCats[prodId])) {
                                    productWillBeHidden = false;
                                }
                            }
                            if (productWillBeHidden) {
                                // скрываем товар
                                $('#pr'+prodId).hide();
                            } else {
                                // нашли
                                platfWillBeHidden = false;
                                vendWillBeHidden = false;
                            }
                        }
                    } else {
                        platfWillBeHidden = false;
                        vendWillBeHidden = false;
                    }

                    if (vendWillBeHidden) {
                        $('#'+platf+'_'+vendId).hide();
                    }
                }
            } else {
                // если платф. выбрана, то платформы, которые не выбраны, будут скрыты
            }
            if (platfWillBeHidden) {
                $('.platf_'+platf).hide();
            } else {
                noResults = false;
            }
        }

        if (noResults) {
            $('#no_results').show();
        }
        updateNotes();
        document.body.style.cursor = 'default';
    });



    $(".items-platf a").on('click', function (e) {
        e.preventDefault();

        $('#platf').val($(this).text());
        $(".items-platf a").removeClass('active');
        $(this).addClass('active');
        $("#filter_form").submit();
//        $("#filter_form")

    });


    $("#filter-clean").on('click', function (e) {
        e.preventDefault();
        $('#search_text').val('');
        $('#platf').val('Все');
        $(".items-platf a").removeClass('active');
        $(".items-platf a:first").addClass('active');

        categoriesTree.clearSelection()
// показываем все скрытие строчки (в том числе те, которые скрывались по id
        $('.platf').show();
        $('.cat-rows').show();
        updateNotes();

// убираем все GET параметры в URL
        const urlObj = new URL(window.location.href);
        if (urlObj.search) {
            urlObj.search = '';
            //urlObj.hash = '';
            window.history.pushState({}, document.title, urlObj.toString());
        }
    });

    function updateNotes(){
        let noteCounter = 0;
        let currentNoteCounter = 0;
        let visibleNotes = {};
        $('#notes .note-item').hide();
        $('.note-link').each(function(){
            let noteNum = $(this).data('note');
            if ($(this).is(':visible')) {
                if (visibleNotes[noteNum]) {
                    currentNoteCounter = visibleNotes[noteNum];
                } else {
                    noteCounter++;
                    visibleNotes[noteNum] = noteCounter;
                    currentNoteCounter = noteCounter;
                }
                $(this).find('span').text(currentNoteCounter);
                $('#note'+noteNum).show();
                $('#note'+noteNum+' span').text(currentNoteCounter);
            }
        });
        if (noteCounter > 0) {
            $('#notes').show();
        }else {
            $('#notes').hide();
        }
    }


//  пример параметров: ?s=Гарант&platform=x86_64&c=200,201,202
    const params = new Proxy(new URLSearchParams(window.location.search), {
        get: (searchParams, prop) => searchParams.get(prop),
    });
    if (params.s || params.platform || params.c) {
        if (params.s) {
            $('#search_text').val(params.s);
        }
        if (params.platform) {
            $('#platf').val(params.platform);
            $(".items-platf a").removeClass('active');
            $('.items-platf a').each(function() {
                if (params.platform === $(this).text()) {
                    $(this).addClass('active');
                }
            });
        }
        if (params.c) {
            categoriesTree.setSelection(params.c.split(','))
        }
        $("#filter_form").submit();
    }

// копирование URL с GET параметрами текущего фильтра
    $('#filter-copy').on('click', function (event) {
        event.preventDefault();
        let url = window.location.origin + window.location.pathname;
        let params = [];
        if ($('#search_text').val()) {
            params.push('s=' + $('#search_text').val().replace(/ /g,'%20'));
        }
        if ($('#platf').val() && $('#platf').val() !== 'Все') {
            params.push('platform=' + $('#platf').val());
        }
        let catIds = categoriesTree.getSelectedIds();
        if (catIds) {
            params.push('c=' + catIds.join(','));
        }
        if (params.length !== 0) {
            url = url + '?' + params.join('&');
        }
        const copyContent = async () => {
            try {
                await navigator.clipboard.writeText(url);
                $('.tooltip').text('Cсылка скопирована в буфер обмена');
                $('.tooltip').show();
                $('.tooltip').delay(3200).fadeOut(300);
            } catch (err) {
//                console.error('Failed to copy: ', err);
                $('.tooltip').text('Ошибка: cсылка не скопирована в буфер');
                $('.tooltip').show();
                $('.tooltip').delay(3200).fadeOut(300);
            }
        }
        copyContent();
    });
});
