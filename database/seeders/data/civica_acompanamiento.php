<?php

/**
 * Habilidades de Cívica y Acompañamiento Integral en el Aula.
 * Guía de acompañamiento socioemocional, Ministerio de Educación, 2024.
 *
 * @return array{
 *     grupos: array<string, list<string>>,
 *     criterios: list<array{codigo: string, descripcion: string, destrezas: list<array{codigo: string, descripcion: string, imprescindible: bool, logros: array<string, string>}>}>
 * }
 */
return [
    'grupos' => [
        'inicial' => ['Inicial 1', 'Inicial 2'],
        'preparatoria' => ['Preparatoria'],
        'elemental' => ['Básica Elemental'],
        'media' => ['Básica Media'],
        'superior' => ['Básica Superior'],
        'bachillerato' => ['Bachillerato General Unificado'],
    ],
    'criterios' => [
        [
            'codigo' => 'CE.CIV.COG',
            'descripcion' => 'Habilidades cognitivas. Autoconocimiento, pensamiento crítico y toma de decisiones son obligatorias. La institución elige una habilidad cognitiva más.',
            'destrezas' => [
                [
                    'codigo' => 'CIV.01',
                    'descripcion' => 'Autoconocimiento: conocimiento de uno mismo que llega tras reconocerse como persona individual con sus cualidades particulares.',
                    'imprescindible' => true,
                    'logros' => [
                        'inicial' => 'Identifica características personales y preferencias individuales en actividades escolares.',
                        'preparatoria' => 'Reconoce sus fortalezas para actividades lúdicas.',
                        'elemental' => 'Describe sus estados de ánimo, sus fortalezas y preferencias.',
                        'media' => 'Reflexiona sobre las propias experiencias, valores y creencias.',
                        'superior' => 'Reflexiona sobre su identidad personal, identifica su estilo de aprendizaje y la manera en que influye en la construcción de sus conocimientos.',
                        'bachillerato' => 'Explora y cuestiona las propias creencias, valores y perspectivas, y muestra una identidad coherente con sus intereses y aspiraciones.',
                    ],
                ],
                [
                    'codigo' => 'CIV.02',
                    'descripcion' => 'Pensamiento crítico: habilidad de analizar, evaluar y cuestionar de manera reflexiva ideas, información, argumentos y situaciones.',
                    'imprescindible' => true,
                    'logros' => [
                        'inicial' => 'Hace preguntas para dar solución a desafíos simples que requieran reflexión.',
                        'preparatoria' => 'Hace preguntas para dar solución a desafíos simples que requieran reflexión.',
                        'elemental' => 'Indaga información sencilla y respalda sus opiniones.',
                        'media' => 'Genera soluciones creativas a problemas sencillos valiéndose de información.',
                        'superior' => 'Analiza la validez de la información y genera soluciones creativas a problemas complejos.',
                        'bachillerato' => 'Investiga temas complejos y evalúa la fiabilidad de las fuentes para dar opiniones informadas.',
                    ],
                ],
                [
                    'codigo' => 'CIV.03',
                    'descripcion' => 'Manejo de problemas: habilidad para identificar, analizar y resolver los problemas de manera efectiva.',
                    'imprescindible' => false,
                    'logros' => [
                        'inicial' => 'Identifica situaciones problemáticas cotidianas simples y busca soluciones sencillas con la guía de un adulto.',
                        'preparatoria' => 'Describe situaciones problemáticas cotidianas simples y busca soluciones sencillas con la guía de un adulto.',
                        'elemental' => 'Describe problemas cotidianos simples y propone posibles soluciones, considerando las consecuencias.',
                        'media' => 'Identifica las causas y consecuencias de problemas cotidianos y propone soluciones creativas y viables.',
                        'superior' => 'Identifica problemas cotidianos complejos, propone soluciones y evalúa su efectividad.',
                        'bachillerato' => 'Identifica problemas en diferentes contextos, plantea soluciones y reflexiona sobre el proceso y los resultados obtenidos.',
                    ],
                ],
                [
                    'codigo' => 'CIV.04',
                    'descripcion' => 'Toma de decisiones: habilidad de analizar y evaluar situaciones considerando necesidades, valores, motivaciones, influencias y posibles consecuencias, en la propia vida y en la de otras personas.',
                    'imprescindible' => true,
                    'logros' => [
                        'inicial' => 'Reconoce preferencias personales para elegir opciones de alimentación, juego y vestido.',
                        'preparatoria' => 'Expresa de forma sencilla el porqué de sus elecciones de alimentación, juego y vestido.',
                        'elemental' => 'Identifica diferentes opciones y consecuencias a corto plazo, y su responsabilidad al elegirlas.',
                        'media' => 'Analiza opciones e identifica sus responsabilidades al elegirlas.',
                        'superior' => 'Se proyecta hacia el futuro e identifica lo que necesita para llegar a sus metas y decidir sobre su vocación.',
                        'bachillerato' => 'Aborda decisiones para su futuro considerando metas, valores, habilidades, oportunidades e implicaciones éticas, y evalúa las consecuencias a largo plazo.',
                    ],
                ],
                [
                    'codigo' => 'CIV.05',
                    'descripcion' => 'Pensamiento creativo: capacidad de generar ideas nuevas, originales e innovadoras que resuelvan problemas y conflictos o exploren oportunidades.',
                    'imprescindible' => false,
                    'logros' => [
                        'inicial' => 'Participa en actividades artísticas de su elección y expresa ideas originales.',
                        'preparatoria' => 'Expresa ideas y soluciones originales en actividades artísticas y en el juego.',
                        'elemental' => 'Genera ideas y soluciones diversas en actividades de arte, música y juego.',
                        'media' => 'Genera ideas originales para sus expresiones artísticas y para la resolución de problemas y conflictos.',
                        'superior' => 'Explora formas de expresión artística y comunicación, y usa la creatividad para soluciones innovadoras.',
                        'bachillerato' => 'Experimenta con diferentes medios y técnicas, y aplica la creatividad en proyectos y en la resolución de problemas desafiantes.',
                    ],
                ],
            ],
        ],
        [
            'codigo' => 'CE.CIV.SOC',
            'descripcion' => 'Habilidades sociales. El manejo de conflictos es obligatorio. La institución elige tres habilidades sociales más.',
            'destrezas' => [
                [
                    'codigo' => 'CIV.06',
                    'descripcion' => 'Trabajo en equipo: capacidad de colaborar y trabajar de manera efectiva en grupos diversos.',
                    'imprescindible' => false,
                    'logros' => [
                        'inicial' => 'Participa en actividades grupales simples, comparte materiales y se turna para interactuar con otros.',
                        'preparatoria' => 'Participa en actividades grupales simples, comparte materiales, se turna y respeta las normas del grupo.',
                        'elemental' => 'Colabora en actividades grupales, comparte ideas, escucha a los demás y respeta las normas del grupo.',
                        'media' => 'Colabora de manera efectiva, respeta y valora la diversidad de opiniones para lograr metas comunes.',
                        'superior' => 'Respeta la diversidad de opiniones para asumir y organizar roles en la consecución de metas comunes.',
                        'bachillerato' => 'Trabaja en proyectos multidisciplinarios, enfrenta desafíos de manera colaborativa y promueve una comunicación abierta y respetuosa.',
                    ],
                ],
                [
                    'codigo' => 'CIV.07',
                    'descripcion' => 'Conciencia social: capacidad para comprender las perspectivas de las demás personas, considerando la diversidad de contextos, culturas y personas.',
                    'imprescindible' => false,
                    'logros' => [
                        'inicial' => 'Reconoce necesidades de los demás en situaciones de interacción social.',
                        'preparatoria' => 'Reconoce y respeta las necesidades de los demás, y practica la colaboración en situaciones de interacción social.',
                        'elemental' => 'Respeta las reglas de convivencia y muestra actitudes de respeto y consideración hacia las demás personas.',
                        'media' => 'Comprende y valora la diversidad, identifica problemas de injusticia y discriminación, y promueve la inclusión.',
                        'superior' => 'Comprende las interacciones sociales y culturales, y valora la equidad y la justicia en las relaciones con los demás.',
                        'bachillerato' => 'Comprende interacciones sociales y globales, y se involucra en iniciativas de cambio social y de respeto a los derechos humanos.',
                    ],
                ],
                [
                    'codigo' => 'CIV.08',
                    'descripcion' => 'Pensamiento ético: capacidad de tomar decisiones basadas en principios éticos y actuar de manera responsable y respetuosa.',
                    'imprescindible' => false,
                    'logros' => [
                        'inicial' => 'Comprende las normas básicas de convivencia: escuchar y comunicarse con claridad y respeto.',
                        'preparatoria' => 'Comprende las normas básicas de convivencia: escuchar y comunicarse con claridad y respeto.',
                        'elemental' => 'Comprende la importancia de actuar de manera justa y respetuosa.',
                        'media' => 'Relaciona situaciones éticas al considerar las consecuencias de diferentes acciones.',
                        'superior' => 'Analiza cuestiones éticas en la toma de decisiones y actúa de manera responsable y respetuosa en diferentes contextos.',
                        'bachillerato' => 'Reflexiona sobre los valores personales y sociales, evalúa el impacto ético de las acciones y promueve la justicia y la equidad.',
                    ],
                ],
                [
                    'codigo' => 'CIV.09',
                    'descripcion' => 'Empatía: habilidad para comprender y compartir los sentimientos de los demás, y ver las situaciones desde la perspectiva de la otra persona.',
                    'imprescindible' => false,
                    'logros' => [
                        'inicial' => 'Muestra actitudes de cuidado y ayuda hacia las demás personas.',
                        'preparatoria' => 'Demuestra comportamientos amables y considerados en las interacciones sociales.',
                        'elemental' => 'Comprende y respeta las diferencias individuales, y muestra actitudes de apoyo y compasión.',
                        'media' => 'Escucha de forma activa y busca maneras de apoyar y comprender a las demás personas.',
                        'superior' => 'Reconoce y valora las experiencias y perspectivas de las demás personas, y demuestra comportamientos solidarios.',
                        'bachillerato' => 'Demuestra inclusión y respeto hacia la diversidad, y promueve la empatía en la resolución de conflictos.',
                    ],
                ],
                [
                    'codigo' => 'CIV.10',
                    'descripcion' => 'Relaciones interpersonales: habilidad de relacionarse y comunicarse efectivamente con los demás.',
                    'imprescindible' => false,
                    'logros' => [
                        'inicial' => 'Comunica con claridad sus necesidades y escucha las de sus compañeros y compañeras.',
                        'preparatoria' => 'Comunica con claridad sus necesidades y escucha las de sus compañeros y compañeras.',
                        'elemental' => 'Se comunica de forma efectiva y respeta las normas de convivencia en entornos sociales.',
                        'media' => 'Colabora con otras personas, trabaja en equipo para alcanzar metas comunes y resolver conflictos de manera constructiva.',
                        'superior' => 'Muestra respeto, empatía y comprensión, y establece relaciones positivas en diversos contextos sociales.',
                        'bachillerato' => 'Trabaja en equipo, promueve la colaboración y la inclusión, y establece relaciones positivas en diferentes entornos sociales.',
                    ],
                ],
                [
                    'codigo' => 'CIV.11',
                    'descripcion' => 'Manejo de conflictos: habilidad para identificar desacuerdos, y para prevenir y resolver conflictos de manera constructiva y efectiva.',
                    'imprescindible' => true,
                    'logros' => [
                        'inicial' => 'Busca soluciones a conflictos con ayuda de una persona adulta.',
                        'preparatoria' => 'Busca soluciones a conflictos con ayuda de una persona adulta.',
                        'elemental' => 'Reconoce desacuerdos, escucha otras opiniones y aplica estrategias simples de negociación.',
                        'media' => 'Se comunica en situaciones de conflicto y busca soluciones creativas para resolver desacuerdos.',
                        'superior' => 'Analiza diferentes puntos de vista, considera las necesidades de las partes y busca soluciones para resolver desacuerdos.',
                        'bachillerato' => 'Reconoce y analiza las causas de los conflictos, y aplica estrategias para resolverlos de manera justa y respetuosa.',
                    ],
                ],
                [
                    'codigo' => 'CIV.12',
                    'descripcion' => 'Comunicación efectiva: habilidad de comunicar de forma adecuada, respetuosa y clara conceptos, ideas, pensamientos, puntos de vista y necesidades emocionales.',
                    'imprescindible' => false,
                    'logros' => [
                        'inicial' => 'Expresa pensamientos y emociones de manera clara y respetuosa.',
                        'preparatoria' => 'Utiliza un lenguaje adecuado, escucha y expresa ideas de manera coherente.',
                        'elemental' => 'Escucha de forma activa y expresa opiniones de manera respetuosa.',
                        'media' => 'Escucha de forma activa, expresa opiniones de manera respetuosa y adapta el lenguaje al contexto y a la audiencia.',
                        'superior' => 'Usa una comunicación clara y asertiva, argumenta de manera fundamentada y adapta el mensaje.',
                        'bachillerato' => 'Utiliza técnicas de persuasión y negociación, y se adapta a diferentes audiencias y propósitos de comunicación.',
                    ],
                ],
                [
                    'codigo' => 'CIV.13',
                    'descripcion' => 'Conciencia global: capacidad de comprender y valorar la diversidad cultural, promover la justicia social y contribuir al bienestar colectivo.',
                    'imprescindible' => false,
                    'logros' => [
                        'inicial' => 'Reconoce diferentes culturas y tradiciones.',
                        'preparatoria' => 'Describe características de su entorno inmediato y de otros entornos cercanos.',
                        'elemental' => 'Compara diferentes culturas, geografías y formas de vida.',
                        'media' => 'Explora formas de contribuir a un mundo más sostenible y justo.',
                        'superior' => 'Analiza las interconexiones entre regiones y culturas, y aborda temas globales desde una perspectiva crítica y ética.',
                        'bachillerato' => 'Analiza problemas sociales a nivel global y participa en proyectos de impacto social a nivel local.',
                    ],
                ],
            ],
        ],
        [
            'codigo' => 'CE.CIV.EMO',
            'descripcion' => 'Habilidades emocionales. La institución elige una: manejo de emociones y sentimientos, o manejo del estrés y las tensiones.',
            'destrezas' => [
                [
                    'codigo' => 'CIV.14',
                    'descripcion' => 'Manejo de emociones y sentimientos: habilidad de reconocer, regular y expresar de manera adecuada las emociones propias y de los demás.',
                    'imprescindible' => false,
                    'logros' => [
                        'inicial' => 'Reconoce emociones básicas (tristeza, alegría, ira y miedo) en situaciones cotidianas.',
                        'preparatoria' => 'Identifica y nombra emociones, y expresa sentimientos.',
                        'elemental' => 'Reconoce y expresa emociones y sentimientos, e identifica emociones básicas en las otras personas.',
                        'media' => 'Comprende las causas y los efectos de las emociones, y muestra una gestión básica de las propias y de las de otras personas.',
                        'superior' => 'Comprende las causas y los efectos de las emociones, las autorregula y apoya la gestión emocional de otras personas.',
                        'bachillerato' => 'Identifica y gestiona emociones complejas con estrategias de autorregulación, y ofrece apoyo emocional a las demás personas.',
                    ],
                ],
                [
                    'codigo' => 'CIV.15',
                    'descripcion' => 'Manejo del estrés y las tensiones: habilidad de emplear estrategias y técnicas para reducir el estrés y las tensiones.',
                    'imprescindible' => false,
                    'logros' => [
                        'inicial' => 'Participa en actividades de relajación y expresión emocional.',
                        'preparatoria' => 'Participa en actividades de relajación y expresión emocional.',
                        'elemental' => 'Reconoce los desencadenantes del estrés.',
                        'media' => 'Identifica estrategias personales para afrontar situaciones de estrés.',
                        'superior' => 'Comprende los factores estresantes y aplica técnicas de manejo del tiempo y del estrés.',
                        'bachillerato' => 'Utiliza técnicas de relajación y autocuidado, afronta el estrés académico y social, y busca apoyo cuando es necesario.',
                    ],
                ],
            ],
        ],
    ],
];
