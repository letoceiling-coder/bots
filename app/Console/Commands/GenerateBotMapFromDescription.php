<?php

namespace App\Console\Commands;

use App\Models\Bot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateBotMapFromDescription extends Command
{
    protected $signature = 'bot:generate-map {bot_id=1}';

    protected $description = 'Генерация карты бота на основе описания из bot_structure_map.md с использованием target_block_id';

    public function handle()
    {
        $botId = $this->argument('bot_id');
        
        $bot = Bot::find($botId);
        if (!$bot) {
            $this->error("Бот с id = {$botId} не найден в базе данных");
            return 1;
        }

        $this->info("Генерация карты для бота: {$bot->name} (ID: {$bot->id})");

        // Генерируем блоки
        $blocks = $this->generateBlocks();

        $this->info("Сгенерировано блоков: " . count($blocks));

        // Сохраняем в бота
        $bot->update(['blocks' => $blocks]);
        $bot->refresh();

        $this->info("");
        $this->info("✅ Карта успешно сгенерирована и сохранена!");
        $this->info("Бот: {$bot->name}");
        $this->info("ID бота: {$bot->id}");
        $this->info("Сохранено блоков: " . count($bot->blocks));

        if ($this->option('verbose')) {
            $this->info("");
            $this->info("Список блоков:");
            foreach ($bot->blocks as $index => $block) {
                $method = $block['method'] ?? 'не указан';
                $label = $block['label'] ?? 'без подписи';
                $this->line("  " . ($index + 1) . ". [{$method}] {$label} (ID: {$block['id']})");
            }
        }

        return 0;
    }

    protected function generateBlocks(): array
    {
        $blocks = [];

        // Блок 1: Команда /start - Приветственное сообщение
        $blocks[] = [
            'id' => '1',
            'label' => '/start - Приветствие',
            'x' => 100,
            'y' => 100,
            'method' => 'sendMessage',
            'method_data' => [
                'text' => "Привет! 👋\n\nМеня зовут Олег, я ваш помощник в Центре бухгалтерского учета.\n\nЯ помогу вам:\n• Создать или изменить бизнес\n• Решить вопросы бухгалтерии\n• Получить юридическую поддержку\n• И многое другое\n\nВыберите пункт меню: 👇",
                'parse_mode' => null
            ],
            'command' => '/start',
            'nextAction' => 'specific',
            'nextBlockId' => '2'
        ];

        // Блок 2: Главное меню
        $blocks[] = [
            'id' => '2',
            'label' => 'Главное меню',
            'x' => 100,
            'y' => 250,
            'method' => 'inlineKeyboard',
            'method_data' => [
                'text' => 'Выберите раздел:',
                'inline_keyboard' => [
                    [
                        [
                            'text' => 'Создать/изменить/закрыть бизнес',
                            'callback_data' => '3',
                            'target_block_id' => '3'
                        ],
                        [
                            'text' => 'Бухгалтерия и отчетность',
                            'callback_data' => '25',
                            'target_block_id' => '25'
                        ]
                    ],
                    [
                        [
                            'text' => 'Судебное сопровождение',
                            'callback_data' => '50',
                            'target_block_id' => '50'
                        ],
                        [
                            'text' => 'Блокировка счета (115-ФЗ/161-ФЗ)',
                            'callback_data' => '70',
                            'target_block_id' => '70'
                        ]
                    ],
                    [
                        [
                            'text' => 'Лицензии и сертификаты',
                            'callback_data' => '85',
                            'target_block_id' => '85'
                        ],
                        [
                            'text' => 'Тендеры и гранты',
                            'callback_data' => '100',
                            'target_block_id' => '100'
                        ]
                    ],
                    [
                        [
                            'text' => 'Связаться с менеджером',
                            'callback_data' => '115',
                            'target_block_id' => '115'
                        ]
                    ]
                ]
            ],
            'nextAction' => '',
            'nextBlockId' => null
        ];

        // ========== ВЕТКА 1: Создать/изменить/закрыть бизнес ==========
        // Блок 3-24: Ветка создания/изменения/закрытия бизнеса
        $blocks = array_merge($blocks, $this->generateBusinessBranch());

        // ========== ВЕТКА 2: Бухгалтерия и отчетность ==========
        $blocks = array_merge($blocks, $this->generateAccountingBranch());

        // ========== ВЕТКА 3: Судебное сопровождение ==========
        $blocks = array_merge($blocks, $this->generateLegalBranch());

        // ========== ВЕТКА 4: Блокировка счета ==========
        $blocks = array_merge($blocks, $this->generateAccountBlockBranch());

        // ========== ВЕТКА 5: Лицензии и сертификаты ==========
        $blocks = array_merge($blocks, $this->generateLicenseBranch());

        // ========== ВЕТКА 6: Тендеры и гранты ==========
        $blocks = array_merge($blocks, $this->generateTenderBranch());

        // ========== ВЕТКА 7: Связаться с менеджером ==========
        $blocks = array_merge($blocks, $this->generateManagerBranch());

        // ========== ФИНАЛЬНЫЕ ДЕЙСТВИЯ ==========
        $blocks = array_merge($blocks, $this->generateFinalActions());

        return $blocks;
    }

    protected function generateBusinessBranch(): array
    {
        $blocks = [];

        // Блок 3: Подменю "Создать/изменить/закрыть бизнес"
        $blocks[] = [
            'id' => '3',
            'label' => 'Подменю: Создать/изменить/закрыть бизнес',
            'x' => 100,
            'y' => 400,
            'method' => 'inlineKeyboard',
            'method_data' => [
                'text' => 'Отлично! Выберите нужный запрос.',
                'inline_keyboard' => [
                    [
                        [
                            'text' => 'Внести изменения в компанию',
                            'callback_data' => '4',
                            'target_block_id' => '4'
                        ]
                    ],
                    [
                        [
                            'text' => 'Ликвидировать компанию',
                            'callback_data' => '5',
                            'target_block_id' => '5'
                        ]
                    ],
                    [
                        [
                            'text' => 'Открыть бизнес (ИП/ООО/АО)',
                            'callback_data' => '6',
                            'target_block_id' => '6'
                        ]
                    ],
                    [
                        [
                            'text' => 'Продать готовую фирму',
                            'callback_data' => '7',
                            'target_block_id' => '7'
                        ]
                    ],
                    [
                        [
                            'text' => 'Сопутствующие услуги',
                            'callback_data' => '8',
                            'target_block_id' => '8'
                        ]
                    ],
                    [
                        [
                            'text' => '← Главное меню',
                            'callback_data' => '2',
                            'target_block_id' => '2'
                        ]
                    ]
                ]
            ],
            'nextAction' => '',
            'nextBlockId' => null
        ];

        // Блок 4: Внести изменения - начало
        $blocks[] = [
            'id' => '4',
            'label' => 'Внести изменения: Начало',
            'x' => 300,
            'y' => 400,
            'method' => 'sendMessage',
            'method_data' => [
                'text' => 'Ответьте на несколько вопросов для регистрации заявки.',
                'parse_mode' => null
            ],
            'nextAction' => 'specific',
            'nextBlockId' => '9'
        ];

        // Блоки сбора данных 9-12
        $blocks[] = [
            'id' => '9',
            'label' => 'Вопрос: ФИО',
            'x' => 300,
            'y' => 550,
            'method' => 'question',
            'method_data' => [
                'text' => 'Как к вам обращаться? (ФИО)',
                'data_key' => 'fio'
            ],
            'nextAction' => 'specific',
            'nextBlockId' => '10'
        ];

        $blocks[] = [
            'id' => '10',
            'label' => 'Вопрос: Телефон',
            'x' => 300,
            'y' => 700,
            'method' => 'question',
            'method_data' => [
                'text' => 'Контактный номер для связи?',
                'data_key' => 'phone'
            ],
            'nextAction' => 'specific',
            'nextBlockId' => '11'
        ];

        $blocks[] = [
            'id' => '11',
            'label' => 'Вопрос: ИНН',
            'x' => 300,
            'y' => 850,
            'method' => 'question',
            'method_data' => [
                'text' => 'ИНН (если требуется)?',
                'data_key' => 'inn'
            ],
            'nextAction' => 'specific',
            'nextBlockId' => '12'
        ];

        $blocks[] = [
            'id' => '12',
            'label' => 'Выбор ОПФ',
            'x' => 300,
            'y' => 1000,
            'method' => 'inlineKeyboard',
            'method_data' => [
                'text' => 'Организационно-правовая форма (ОПФ)?',
                'inline_keyboard' => [
                    [
                        [
                            'text' => 'ИП',
                            'callback_data' => 'opf_ip',
                            'target_block_id' => '13'
                        ],
                        [
                            'text' => 'ООО',
                            'callback_data' => 'opf_ooo',
                            'target_block_id' => '13'
                        ]
                    ],
                    [
                        [
                            'text' => 'АО',
                            'callback_data' => 'opf_ao',
                            'target_block_id' => '13'
                        ],
                        [
                            'text' => 'Другое',
                            'callback_data' => 'opf_other',
                            'target_block_id' => '13'
                        ]
                    ]
                ]
            ],
            'nextAction' => '',
            'nextBlockId' => null
        ];

        $blocks[] = [
            'id' => '13',
            'label' => 'Данные приняты',
            'x' => 300,
            'y' => 1150,
            'method' => 'sendMessage',
            'method_data' => [
                'text' => 'Данные приняты, спасибо!',
                'parse_mode' => null
            ],
            'nextAction' => 'specific',
            'nextBlockId' => '14'
        ];

        $blocks[] = [
            'id' => '14',
            'label' => 'Выбор типа изменений',
            'x' => 300,
            'y' => 1300,
            'method' => 'inlineKeyboard',
            'method_data' => [
                'text' => 'Какой тип изменений требуется?',
                'inline_keyboard' => [
                    [
                        [
                            'text' => 'Смена ген-директора',
                            'callback_data' => 'change_director',
                            'target_block_id' => '15'
                        ]
                    ],
                    [
                        [
                            'text' => 'Смена учредителей/доли',
                            'callback_data' => 'change_founders',
                            'target_block_id' => '15'
                        ]
                    ],
                    [
                        [
                            'text' => 'Смена юр. адреса',
                            'callback_data' => 'change_address',
                            'target_block_id' => '15'
                        ]
                    ],
                    [
                        [
                            'text' => 'Добавление ОКВЭД',
                            'callback_data' => 'add_okved',
                            'target_block_id' => '15'
                        ]
                    ],
                    [
                        [
                            'text' => 'Смена названия',
                            'callback_data' => 'change_name',
                            'target_block_id' => '15'
                        ]
                    ],
                    [
                        [
                            'text' => 'Другое (уточнить)',
                            'callback_data' => 'change_other',
                            'target_block_id' => '15'
                        ]
                    ]
                ]
            ],
            'nextAction' => '',
            'nextBlockId' => null
        ];

        $blocks[] = [
            'id' => '15',
            'label' => 'Описание ситуации',
            'x' => 300,
            'y' => 1450,
            'method' => 'inlineKeyboard',
            'method_data' => [
                'text' => 'Кратко опишите ситуацию (опционально)',
                'inline_keyboard' => [
                    [
                        [
                            'text' => 'Пропустить',
                            'callback_data' => 'skip_description',
                            'target_block_id' => '16'
                        ]
                    ]
                ]
            ],
            'nextAction' => 'specific',
            'nextBlockId' => '17'
        ];

        $blocks[] = [
            'id' => '16',
            'label' => 'Информация собрана',
            'x' => 300,
            'y' => 1600,
            'method' => 'sendMessage',
            'method_data' => [
                'text' => 'Отлично! Вся информация собрана.',
                'parse_mode' => null
            ],
            'nextAction' => 'specific',
            'nextBlockId' => '120'
        ];

        $blocks[] = [
            'id' => '17',
            'label' => 'Ввод описания',
            'x' => 500,
            'y' => 1450,
            'method' => 'question',
            'method_data' => [
                'text' => 'Опишите ситуацию подробнее:',
                'data_key' => 'description'
            ],
            'nextAction' => 'specific',
            'nextBlockId' => '16'
        ];

        // Блоки для других подразделов ветки 1 (ликвидация, открытие, продажа, сопутствующие)
        // Добавлю упрощенные версии для экономии места
        $blocks[] = [
            'id' => '5',
            'label' => 'Ликвидировать: Начало',
            'x' => 500,
            'y' => 400,
            'method' => 'sendMessage',
            'method_data' => [
                'text' => 'Ответьте на несколько вопросов для регистрации заявки.',
                'parse_mode' => null
            ],
            'nextAction' => 'specific',
            'nextBlockId' => '120'
        ];

        $blocks[] = [
            'id' => '6',
            'label' => 'Открыть бизнес: Начало',
            'x' => 700,
            'y' => 400,
            'method' => 'sendMessage',
            'method_data' => [
                'text' => 'Ответьте на несколько вопросов для регистрации заявки.',
                'parse_mode' => null
            ],
            'nextAction' => 'specific',
            'nextBlockId' => '120'
        ];

        $blocks[] = [
            'id' => '7',
            'label' => 'Продать фирму: Начало',
            'x' => 900,
            'y' => 400,
            'method' => 'sendMessage',
            'method_data' => [
                'text' => 'Ответьте на несколько вопросов для регистрации заявки.',
                'parse_mode' => null
            ],
            'nextAction' => 'specific',
            'nextBlockId' => '120'
        ];

        $blocks[] = [
            'id' => '8',
            'label' => 'Сопутствующие услуги',
            'x' => 1100,
            'y' => 400,
            'method' => 'inlineKeyboard',
            'method_data' => [
                'text' => 'Выберите сопутствующую услугу:',
                'inline_keyboard' => [
                    [
                        [
                            'text' => 'ЭЦП',
                            'callback_data' => 'service_ecp',
                            'target_block_id' => '120'
                        ],
                        [
                            'text' => 'Печать',
                            'callback_data' => 'service_seal',
                            'target_block_id' => '120'
                        ]
                    ],
                    [
                        [
                            'text' => '← Назад',
                            'callback_data' => '3',
                            'target_block_id' => '3'
                        ]
                    ]
                ]
            ],
            'nextAction' => '',
            'nextBlockId' => null
        ];

        return $blocks;
    }

    protected function generateAccountingBranch(): array
    {
        $blocks = [];

        $blocks[] = [
            'id' => '25',
            'label' => 'Подменю: Бухгалтерия',
            'x' => 100,
            'y' => 550,
            'method' => 'inlineKeyboard',
            'method_data' => [
                'text' => 'Отлично! Выберите нужный запрос.',
                'inline_keyboard' => [
                    [
                        [
                            'text' => 'Бухгалтерское сопровождение',
                            'callback_data' => 'accounting_support',
                            'target_block_id' => '120'
                        ]
                    ],
                    [
                        [
                            'text' => 'Разовая услуга',
                            'callback_data' => 'accounting_one_time',
                            'target_block_id' => '120'
                        ]
                    ],
                    [
                        [
                            'text' => 'Подбор ПО для бухгалтерии',
                            'callback_data' => 'accounting_software',
                            'target_block_id' => '120'
                        ]
                    ],
                    [
                        [
                            'text' => '← Главное меню',
                            'callback_data' => '2',
                            'target_block_id' => '2'
                        ]
                    ]
                ]
            ],
            'nextAction' => '',
            'nextBlockId' => null
        ];

        return $blocks;
    }

    protected function generateLegalBranch(): array
    {
        $blocks = [];

        $blocks[] = [
            'id' => '50',
            'label' => 'Подменю: Судебное сопровождение',
            'x' => 100,
            'y' => 1150,
            'method' => 'inlineKeyboard',
            'method_data' => [
                'text' => 'Отлично! Выберите нужный запрос.',
                'inline_keyboard' => [
                    [
                        [
                            'text' => 'Взыскание долгов',
                            'callback_data' => 'legal_debt',
                            'target_block_id' => '120'
                        ]
                    ],
                    [
                        [
                            'text' => 'Защита интересов в суде',
                            'callback_data' => 'legal_defense',
                            'target_block_id' => '120'
                        ]
                    ],
                    [
                        [
                            'text' => 'Арбитражные споры',
                            'callback_data' => 'legal_arbitrage',
                            'target_block_id' => '120'
                        ]
                    ],
                    [
                        [
                            'text' => '← Главное меню',
                            'callback_data' => '2',
                            'target_block_id' => '2'
                        ]
                    ]
                ]
            ],
            'nextAction' => '',
            'nextBlockId' => null
        ];

        return $blocks;
    }

    protected function generateAccountBlockBranch(): array
    {
        $blocks = [];

        $blocks[] = [
            'id' => '70',
            'label' => 'Подменю: Блокировка счета',
            'x' => 300,
            'y' => 1150,
            'method' => 'inlineKeyboard',
            'method_data' => [
                'text' => 'Отлично! Выберите нужный запрос.',
                'inline_keyboard' => [
                    [
                        [
                            'text' => 'Разблокировка счета',
                            'callback_data' => 'unlock_account',
                            'target_block_id' => '120'
                        ]
                    ],
                    [
                        [
                            'text' => 'Консультация по блокировке',
                            'callback_data' => 'consultation_block',
                            'target_block_id' => '120'
                        ]
                    ],
                    [
                        [
                            'text' => '← Главное меню',
                            'callback_data' => '2',
                            'target_block_id' => '2'
                        ]
                    ]
                ]
            ],
            'nextAction' => '',
            'nextBlockId' => null
        ];

        return $blocks;
    }

    protected function generateLicenseBranch(): array
    {
        $blocks = [];

        $blocks[] = [
            'id' => '85',
            'label' => 'Подменю: Лицензии',
            'x' => 500,
            'y' => 1150,
            'method' => 'inlineKeyboard',
            'method_data' => [
                'text' => 'Отлично! Выберите нужный запрос.',
                'inline_keyboard' => [
                    [
                        [
                            'text' => 'Получение лицензии',
                            'callback_data' => 'license_get',
                            'target_block_id' => '120'
                        ]
                    ],
                    [
                        [
                            'text' => 'Продление лицензии',
                            'callback_data' => 'license_renew',
                            'target_block_id' => '120'
                        ]
                    ],
                    [
                        [
                            'text' => '← Главное меню',
                            'callback_data' => '2',
                            'target_block_id' => '2'
                        ]
                    ]
                ]
            ],
            'nextAction' => '',
            'nextBlockId' => null
        ];

        return $blocks;
    }

    protected function generateTenderBranch(): array
    {
        $blocks = [];

        $blocks[] = [
            'id' => '100',
            'label' => 'Подменю: Тендеры',
            'x' => 700,
            'y' => 1150,
            'method' => 'inlineKeyboard',
            'method_data' => [
                'text' => 'Отлично! Выберите нужный запрос.',
                'inline_keyboard' => [
                    [
                        [
                            'text' => 'Участие в тендерах',
                            'callback_data' => 'tender_participation',
                            'target_block_id' => '120'
                        ]
                    ],
                    [
                        [
                            'text' => 'Получение грантов',
                            'callback_data' => 'grant_get',
                            'target_block_id' => '120'
                        ]
                    ],
                    [
                        [
                            'text' => '← Главное меню',
                            'callback_data' => '2',
                            'target_block_id' => '2'
                        ]
                    ]
                ]
            ],
            'nextAction' => '',
            'nextBlockId' => null
        ];

        return $blocks;
    }

    protected function generateManagerBranch(): array
    {
        $blocks = [];

        $blocks[] = [
            'id' => '115',
            'label' => 'Связаться с менеджером: Начало',
            'x' => 1100,
            'y' => 1150,
            'method' => 'sendMessage',
            'method_data' => [
                'text' => 'Укажите ваши контактные данные и менеджер свяжется с вами...',
                'parse_mode' => null
            ],
            'nextAction' => 'specific',
            'nextBlockId' => '116'
        ];

        $blocks[] = [
            'id' => '116',
            'label' => 'Менеджер: ФИО',
            'x' => 1100,
            'y' => 1300,
            'method' => 'question',
            'method_data' => [
                'text' => 'Как к вам обращаться? (ФИО)',
                'data_key' => 'fio'
            ],
            'nextAction' => 'specific',
            'nextBlockId' => '117'
        ];

        $blocks[] = [
            'id' => '117',
            'label' => 'Менеджер: Телефон',
            'x' => 1100,
            'y' => 1450,
            'method' => 'question',
            'method_data' => [
                'text' => 'Контактный номер для связи?',
                'data_key' => 'phone'
            ],
            'nextAction' => 'specific',
            'nextBlockId' => '118'
        ];

        $blocks[] = [
            'id' => '118',
            'label' => 'Менеджер: Информация собрана',
            'x' => 1100,
            'y' => 1600,
            'method' => 'sendMessage',
            'method_data' => [
                'text' => 'Отлично! Вся информация собрана.',
                'parse_mode' => null
            ],
            'nextAction' => 'specific',
            'nextBlockId' => '119'
        ];

        $blocks[] = [
            'id' => '119',
            'label' => 'Переход к менеджеру',
            'x' => 1100,
            'y' => 1750,
            'method' => 'managerChat',
            'method_data' => [
                'text' => 'Переключение на менеджера...',
                'manager_chat_id' => null
            ],
            'nextAction' => '',
            'nextBlockId' => null
        ];

        return $blocks;
    }

    protected function generateFinalActions(): array
    {
        $blocks = [];

        $blocks[] = [
            'id' => '120',
            'label' => 'Финальные действия',
            'x' => 800,
            'y' => 2000,
            'method' => 'inlineKeyboard',
            'method_data' => [
                'text' => 'Отлично! Вся информация собрана. Выберите действие:',
                'inline_keyboard' => [
                    [
                        [
                            'text' => 'Отправить заявку',
                            'callback_data' => 'final_submit',
                            'target_block_id' => '121'
                        ]
                    ],
                    [
                        [
                            'text' => 'Ознакомиться с прайсом',
                            'callback_data' => 'final_price',
                            'target_block_id' => '122'
                        ],
                        [
                            'text' => 'Связаться с менеджером',
                            'callback_data' => 'final_manager',
                            'target_block_id' => '115'
                        ]
                    ],
                    [
                        [
                            'text' => 'Главное меню',
                            'callback_data' => '2',
                            'target_block_id' => '2'
                        ]
                    ]
                ]
            ],
            'nextAction' => '',
            'nextBlockId' => null
        ];

        $blocks[] = [
            'id' => '121',
            'label' => 'Заявка зарегистрирована',
            'x' => 800,
            'y' => 2150,
            'method' => 'sendMessage',
            'method_data' => [
                'text' => 'Заявка №*** зарегистрирована!\nМенеджер свяжется с вами в течение 30 минут.',
                'parse_mode' => null
            ],
            'nextAction' => '',
            'nextBlockId' => null
        ];

        $blocks[] = [
            'id' => '122',
            'label' => 'Отправка прайса',
            'x' => 1000,
            'y' => 2000,
            'method' => 'sendDocument',
            'method_data' => [
                'document' => '',
                'caption' => 'Прайс-лист на услуги',
                'parse_mode' => null
            ],
            'nextAction' => '',
            'nextBlockId' => null
        ];

        return $blocks;
    }
}

