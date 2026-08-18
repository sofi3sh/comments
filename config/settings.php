<?php

return [

    'groups' => [

        'footer' => [
            'label' => 'settings.groups.footer',
        ],

        'contacts' => [
            'label' => 'settings.groups.contacts',
        ],

        'social' => [
            'label' => 'settings.groups.social',
        ],

        'system' => [
            'label' => 'settings.groups.system',
        ],

    ],

    'definitions' => [

        'footer.text_1' => [
            'group' => 'footer',
            'type'  => 'localized_text',
            'label' => 'settings.footer-text_1.label',
            'description' => 'settings.footer-text_1.description',
            'default' => [
                'uk' => '«COMMENTS» та «COMMENTS.UA» є зареєстрованими торговими марками в Україні. Цитування або відтворення матеріалів з інформаційно-аналітичного порталу «Коментарі» (comments.ua) без зазначення активного гіперпосилання на www.comments.ua (не закритого від індексації пошуковими системами) безпосередньо в тексті новини вважатиметься порушенням Закону України «Про авторське право та суміжні права».',
                'en' => '"COMMENTS" and «COMMENTS.UA» are registered trademarks in Ukraine. Quoting or reproducing materials from the information and analytical portal "Comments" (comments.ua) without indicating an active hyperlink to www.comments.ua (not closed from indexing by search engines) directly in the text of the news will be considered a violation of the Law of Ukraine "On Copyright and Related Rights".',
                'ru' => '"COMMENTS" и «COMMENTS.UA» являются зарегистрированными товарными знаками в Украине. Цитирование или воспроизведение материалов информационно-аналитического портала «Комментарии» (comments.ua) без указания активной гиперссылки на www.comments.ua (не закрытой для индексации поисковыми системами) непосредственно в тексте новости будет считаться нарушением Закона Украины «Об авторском праве и смежных правах».',
            ],
        ],

        'footer.text_2' => [
            'group' => 'footer',
            'type'  => 'localized_text',
            'label' => 'settings.footer-text_2.label',
            'description' => 'settings.footer-text_2.description',
            'default' => [
                'uk' => 'Новини партнерів публікуються в розділі «Прес-релізи». Рекламні матеріали містять спеціальні позначки. Розділ «Дайджест» може містити інформацію з Інтернету. Ми не несемо відповідальності за точність такої інформації. Призначено для осіб віком від 21 року.',
                'en' => 'Partner news is published in the Press Release section. Advertising materials contain special markings. The Digest section may include information from the Internet. We do not take responsibility for the accuracy of such information. Intended for persons aged 21 and older.',
                'ru' => 'Новости партнеров публикуются в разделе «Пресс-релизы». Рекламные материалы содержат специальные пометки. Раздел «Дайджест» может содержать информацию из Интернета. Мы не несем ответственности за точность такой информации. Предназначено для лиц старше 21 года.',
            ],
        ],

        'footer.copyright' => [
            'group' => 'footer',
            'type' => 'localized_text',
            'label' => 'settings.footer-copyright.label',
            'description' => 'settings.footer-copyright.description',
            'default' => [
                'uk' => 'Copyright',
                'en' => 'Copyright',
                'ru' => 'Copyright',
            ],
        ],

        'contacts.phone' => [
            'group' => 'contacts',
            'type' => 'phone',
            'label' => 'settings.contacts-phone.label',
            'description' => 'settings.contacts-phone.description',
            'default' => [
                'value' => '12345678901',
            ],
        ],

        'contacts.email' => [
            'group' => 'contacts',
            'type' => 'email',
            'label' => 'settings.contacts-email.label',
            'description' => 'settings.contacts-email.description',
            'default' => [
                'value' => 'example@mail.com',
            ],
        ],

        'social.links' => [
            'group' => 'social',
            'type' => 'social_links',
            'label' => 'settings.social-links.label',
            'description' => 'settings.social-links.description',
            'default' => [
                'facebook' => [
                    'enabled' => false,
                    'url' => '',
                ],
                'telegram' => [
                    'enabled' => false,
                    'url' => '',
                ],
                'youtube' => [
                    'enabled' => false,
                    'url' => '',
                ],
                'instagram' => [
                    'enabled' => false,
                    'url' => '',
                ],
                'tiktok' => [
                    'enabled' => false,
                    'url' => '',
                ],
                'twitter' => [
                    'enabled' => false,
                    'url' => '',
                ],
            ],
        ],

        'static.mode' => [
            'group' => 'system',
            'type' => 'boolean',
            'label' => 'settings.static-mode.label',
            'description' => 'settings.static-mode.description',
            'default' => [
                'value' => env('STATIC_CAPTURE_ENABLED', false),
            ],
        ],
    ],
];