<?php

/**
 * Catálogo inicial del Currículo de Educación Inicial y del Currículo
 * integrador de Preparatoria. Sistemas puede completar o ajustar cada
 * criterio, destreza e indicador desde Áreas y asignaturas.
 *
 * @return array<string, array<string, list<array{codigo: string, descripcion: string, destrezas: list<array{codigo: string, descripcion: string, imprescindible?: bool, indicadores: list<array{codigo: string, descripcion: string}>}>}>>>
 */
return [
    'Inicial 1' => [
        'Identidad y autonomía' => [[
            'codigo' => 'CE.I1.IA.1',
            'descripcion' => 'Reconoce su cuerpo y acepta el cuidado del adulto en la alimentación, el aseo y el descanso.',
            'destrezas' => [
                ['codigo' => 'I1.IA.1', 'descripcion' => 'Reconocer partes de su cuerpo cuando el adulto las nombra.', 'indicadores' => [
                    ['codigo' => 'I.I1.IA.1', 'descripcion' => 'Señala su cabeza, sus manos o sus pies cuando se lo piden en el juego.'],
                ]],
                ['codigo' => 'I1.IA.2', 'descripcion' => 'Colaborar en rutinas de aseo, alimentación y descanso.', 'indicadores' => [
                    ['codigo' => 'I.I1.IA.2', 'descripcion' => 'Acepta que lo muden, lo alimenten o lo acuesten y empieza a colaborar.'],
                ]],
            ],
        ]],
        'Convivencia' => [[
            'codigo' => 'CE.I1.CV.1',
            'descripcion' => 'Se vincula con las personas cercanas y empieza a compartir el espacio de juego.',
            'destrezas' => [
                ['codigo' => 'I1.CV.1', 'descripcion' => 'Responder a la mirada, la voz y el contacto de las personas conocidas.', 'indicadores' => [
                    ['codigo' => 'I.I1.CV.1', 'descripcion' => 'Sonríe, se acerca o busca a la persona familiar cuando la ve.'],
                ]],
                ['codigo' => 'I1.CV.2', 'descripcion' => 'Participar un momento en el juego junto a otros niños.', 'indicadores' => [
                    ['codigo' => 'I.I1.CV.2', 'descripcion' => 'Permanece cerca de otros niños y comparte un objeto con ayuda del adulto.'],
                ]],
            ],
        ]],
        'Relaciones con el medio natural y cultural' => [[
            'codigo' => 'CE.I1.MN.1',
            'descripcion' => 'Explora objetos y seres de su entorno con los sentidos.',
            'destrezas' => [
                ['codigo' => 'I1.MN.1', 'descripcion' => 'Explorar objetos con la mirada, las manos y la boca, según su edad.', 'indicadores' => [
                    ['codigo' => 'I.I1.MN.1', 'descripcion' => 'Toma, sacude o mira un objeto para conocerlo.'],
                ]],
                ['codigo' => 'I1.MN.2', 'descripcion' => 'Reaccionar ante sonidos, texturas y cambios del entorno cercano.', 'indicadores' => [
                    ['codigo' => 'I.I1.MN.2', 'descripcion' => 'Gira, se detiene o expresa gusto o disgusto ante un sonido o una textura.'],
                ]],
            ],
        ]],
        'Comprensión y expresión del lenguaje' => [[
            'codigo' => 'CE.I1.LE.1',
            'descripcion' => 'Comunica necesidades y responde a palabras y cantos familiares.',
            'destrezas' => [
                ['codigo' => 'I1.LE.1', 'descripcion' => 'Comunicar hambre, sueño o malestar con gestos, llanto o palabras.', 'indicadores' => [
                    ['codigo' => 'I.I1.LE.1', 'descripcion' => 'Usa un gesto, un sonido o una palabra para pedir algo.'],
                ]],
                ['codigo' => 'I1.LE.2', 'descripcion' => 'Responder a su nombre y a canciones o palabras que escucha a diario.', 'indicadores' => [
                    ['codigo' => 'I.I1.LE.2', 'descripcion' => 'Voltea o responde cuando lo llaman y acompaña un canto conocido.'],
                ]],
            ],
        ]],
        'Expresión artística' => [[
            'codigo' => 'CE.I1.AR.1',
            'descripcion' => 'Disfruta de sonidos, colores y materiales seguros.',
            'destrezas' => [
                ['codigo' => 'I1.AR.1', 'descripcion' => 'Explorar sonidos de su voz, de objetos y de la música.', 'indicadores' => [
                    ['codigo' => 'I.I1.AR.1', 'descripcion' => 'Produce sonidos o se mueve cuando escucha música.'],
                ]],
                ['codigo' => 'I1.AR.2', 'descripcion' => 'Dejar huellas con las manos o con materiales blandos.', 'indicadores' => [
                    ['codigo' => 'I.I1.AR.2', 'descripcion' => 'Mancha, aplasta o arrastra un material y mira el resultado.'],
                ]],
            ],
        ]],
        'Expresión corporal y motricidad' => [[
            'codigo' => 'CE.I1.MO.1',
            'descripcion' => 'Se desplaza y manipula objetos de acuerdo con su momento de desarrollo.',
            'destrezas' => [
                ['codigo' => 'I1.MO.1', 'descripcion' => 'Moverse de un lugar a otro según su posibilidad: rodar, gatear o caminar.', 'indicadores' => [
                    ['codigo' => 'I.I1.MO.1', 'descripcion' => 'Cambia de lugar para alcanzar un juguete o a una persona.'],
                ]],
                ['codigo' => 'I1.MO.2', 'descripcion' => 'Tomar, soltar y encajar objetos grandes.', 'indicadores' => [
                    ['codigo' => 'I.I1.MO.2', 'descripcion' => 'Agarra un objeto y lo suelta o lo mete en un recipiente.'],
                ]],
            ],
        ]],
    ],
    'Inicial 2' => [
        'Identidad y autonomía' => [[
            'codigo' => 'CE.I2.IA.1',
            'descripcion' => 'Se reconoce como niño o niña, comunica datos de su identidad y practica hábitos de cuidado con mayor autonomía.',
            'destrezas' => [
                ['codigo' => 'I2.IA.1', 'descripcion' => 'Comunicar su nombre, su edad y el lugar donde vive.', 'indicadores' => [
                    ['codigo' => 'I.I2.IA.1', 'descripcion' => 'Dice su nombre y algún otro dato personal cuando se presenta.'],
                ]],
                ['codigo' => 'I2.IA.2', 'descripcion' => 'Practicar hábitos de higiene, alimentación y descanso.', 'indicadores' => [
                    ['codigo' => 'I.I2.IA.2', 'descripcion' => 'Se lava las manos, come y descansa siguiendo la rutina, con ayuda ocasional.'],
                ]],
                ['codigo' => 'I2.IA.3', 'descripcion' => 'Reconocer características físicas propias y de las personas de su entorno.', 'indicadores' => [
                    ['codigo' => 'I.I2.IA.3', 'descripcion' => 'Nombra o señala diferencias y semejanzas de su cuerpo y del de otros.'],
                ]],
            ],
        ]],
        'Convivencia' => [[
            'codigo' => 'CE.I2.CV.1',
            'descripcion' => 'Participa en el grupo, acuerda reglas sencillas y respeta los turnos.',
            'destrezas' => [
                ['codigo' => 'I2.CV.1', 'descripcion' => 'Participar en juegos grupales siguiendo reglas y turnos.', 'indicadores' => [
                    ['codigo' => 'I.I2.CV.1', 'descripcion' => 'Espera su turno y acepta una regla simple del juego.'],
                ]],
                ['codigo' => 'I2.CV.2', 'descripcion' => 'Proponer juegos y construir reglas con otros niños.', 'indicadores' => [
                    ['codigo' => 'I.I2.CV.2', 'descripcion' => 'Invita a un compañero y explica cómo quiere jugar.'],
                ]],
            ],
        ]],
        'Relaciones con el medio natural y cultural' => [[
            'codigo' => 'CE.I2.MN.1',
            'descripcion' => 'Observa, explora y describe elementos naturales y culturales de su entorno.',
            'destrezas' => [
                ['codigo' => 'I2.MN.1', 'descripcion' => 'Identificar elementos naturales y objetos creados por las personas.', 'indicadores' => [
                    ['codigo' => 'I.I2.MN.1', 'descripcion' => 'Diferencia, por ejemplo, una planta de un juguete o una herramienta.'],
                ]],
                ['codigo' => 'I2.MN.2', 'descripcion' => 'Describir características de seres y objetos usando los sentidos.', 'indicadores' => [
                    ['codigo' => 'I.I2.MN.2', 'descripcion' => 'Dice el color, el tamaño, el sonido o la textura de lo que explora.'],
                ]],
            ],
        ]],
        'Comprensión y expresión del lenguaje' => [[
            'codigo' => 'CE.I2.LE.1',
            'descripcion' => 'Cuenta experiencias y reconoce su nombre escrito en rótulos cercanos.',
            'destrezas' => [
                ['codigo' => 'I2.LE.1', 'descripcion' => 'Contar de manera oral una experiencia cotidiana.', 'indicadores' => [
                    ['codigo' => 'I.I2.LE.1', 'descripcion' => 'Relata qué hizo, con quién o dónde, aunque lo haga breve.'],
                ]],
                ['codigo' => 'I2.LE.2', 'descripcion' => 'Identificar su nombre en etiquetas, percheros o trabajos.', 'indicadores' => [
                    ['codigo' => 'I.I2.LE.2', 'descripcion' => 'Señala o toma el objeto que tiene su nombre.'],
                ]],
            ],
        ]],
        'Expresión artística' => [[
            'codigo' => 'CE.I2.AR.1',
            'descripcion' => 'Expresa vivencias con el dibujo, el sonido y el juego dramático.',
            'destrezas' => [
                ['codigo' => 'I2.AR.1', 'descripcion' => 'Expresar vivencias mediante el dibujo libre.', 'indicadores' => [
                    ['codigo' => 'I.I2.AR.1', 'descripcion' => 'Dibuja y cuenta qué quiso representar.'],
                ]],
                ['codigo' => 'I2.AR.2', 'descripcion' => 'Explorar sonidos de la voz, del cuerpo y de objetos.', 'indicadores' => [
                    ['codigo' => 'I.I2.AR.2', 'descripcion' => 'Imita o produce sonidos y los usa en un juego o una canción.'],
                ]],
            ],
        ]],
        'Expresión corporal y motricidad' => [[
            'codigo' => 'CE.I2.MO.1',
            'descripcion' => 'Coordina movimientos de equilibrio, desplazamiento y ritmo.',
            'destrezas' => [
                ['codigo' => 'I2.MO.1', 'descripcion' => 'Caminar, correr, saltar y equilibrarse en recorridos sencillos.', 'indicadores' => [
                    ['codigo' => 'I.I2.MO.1', 'descripcion' => 'Completa un recorrido con pasos, saltos o equilibrio, a su ritmo.'],
                ]],
                ['codigo' => 'I2.MO.2', 'descripcion' => 'Participar en rondas y juegos tradicionales.', 'indicadores' => [
                    ['codigo' => 'I.I2.MO.2', 'descripcion' => 'Sigue el ritmo y los movimientos de una ronda conocida.'],
                ]],
            ],
        ]],
    ],
    'Preparatoria' => [
        'Identidad y autonomía' => [
            [
                'codigo' => 'CE.CS.1.1',
                'descripcion' => 'Reconoce que es un ser integral, con características personales, y que forma parte de una familia y de una comunidad.',
                'destrezas' => [
                    ['codigo' => 'CS.1.1.1', 'descripcion' => 'Reconocer que es un ser que siente, piensa, opina y tiene necesidades.', 'indicadores' => [
                        ['codigo' => 'I.CS.1.1.1', 'descripcion' => 'Expresa sus datos personales y reconoce que siente, piensa, opina y tiene necesidades.'],
                    ]],
                    ['codigo' => 'CS.1.1.2', 'descripcion' => 'Comunicar sus nombres, apellidos, edad, teléfono y el lugar donde vive.', 'indicadores' => [
                        ['codigo' => 'I.CS.1.1.2', 'descripcion' => 'Reconoce su historia personal y familiar y que forma parte de una comunidad.'],
                    ]],
                    ['codigo' => 'CS.1.1.3', 'descripcion' => 'Reconocer su historia personal y familiar desde su nacimiento.', 'indicadores' => [
                        ['codigo' => 'I.CS.1.1.3', 'descripcion' => 'Cuenta un momento de su historia con apoyo de fotos, dibujos o relatos.'],
                    ]],
                ],
            ],
            [
                'codigo' => 'CE.CS.1.2',
                'descripcion' => 'Practica con autonomía hábitos de higiene, alimentación, cuidado y seguridad en la casa y en la escuela.',
                'destrezas' => [
                    ['codigo' => 'CS.1.1.7', 'descripcion' => 'Practicar hábitos de alimentación, higiene y cuidado personal con autonomía.', 'indicadores' => [
                        ['codigo' => 'I.CS.1.2.1', 'descripcion' => 'Practica con autonomía hábitos de alimentación, higiene y cuidado personal.'],
                    ]],
                    ['codigo' => 'CS.1.1.10', 'descripcion' => 'Distinguir situaciones de peligro de su entorno para evitar accidentes.', 'indicadores' => [
                        ['codigo' => 'I.CS.1.2.2', 'descripcion' => 'Reconoce peligros cercanos y aplica normas sencillas de seguridad.'],
                    ]],
                ],
            ],
        ],
        'Convivencia' => [[
            'codigo' => 'CE.CS.1.3',
            'descripcion' => 'Reconoce y respeta las diferencias de su entorno, cumple acuerdos y valora los aportes de los demás.',
            'destrezas' => [
                ['codigo' => 'CS.1.2.1', 'descripcion' => 'Reconocer y respetar las diferencias de las personas de su entorno.', 'indicadores' => [
                    ['codigo' => 'I.CS.1.3.1', 'descripcion' => 'Nombra una diferencia entre las personas y la trata con respeto.'],
                ]],
                ['codigo' => 'CS.1.2.2', 'descripcion' => 'Cumplir acuerdos y responsabilidades en la casa y en la escuela.', 'indicadores' => [
                    ['codigo' => 'I.CS.1.3.2', 'descripcion' => 'Respeta un acuerdo del grupo y colabora en una tarea común.'],
                ]],
            ],
        ]],
        'Descubrimiento y comprensión del medio natural y cultural' => [[
            'codigo' => 'CE.CN.1.1',
            'descripcion' => 'Observa, explora y describe seres, objetos y fenómenos de su entorno con los sentidos.',
            'destrezas' => [
                ['codigo' => 'CN.1.1.1', 'descripcion' => 'Observar un objeto o un evento y decir sus rasgos con los sentidos.', 'indicadores' => [
                    ['codigo' => 'I.CN.1.1.1', 'descripcion' => 'Describe color, forma, sonido, olor o textura de lo que observa.'],
                ]],
                ['codigo' => 'CN.1.1.2', 'descripcion' => 'Registrar con símbolos o pictogramas lo que indagó.', 'indicadores' => [
                    ['codigo' => 'I.CN.1.1.2', 'descripcion' => 'Marca un cuadro o un dibujo con el resultado de su exploración.'],
                ]],
            ],
        ]],
        'Relaciones lógico-matemáticas' => [[
            'codigo' => 'CE.M.1.1',
            'descripcion' => 'Compara, cuenta y ubica objetos de situaciones cotidianas del entorno cercano.',
            'destrezas' => [
                ['codigo' => 'M.1.4.1', 'descripcion' => 'Contar colecciones pequeñas de objetos en situaciones de juego.', 'indicadores' => [
                    ['codigo' => 'I.M.1.1.1', 'descripcion' => 'Cuenta objetos uno a uno y dice cuántos hay.'],
                ]],
                ['codigo' => 'M.1.4.2', 'descripcion' => 'Comparar objetos por tamaño, cantidad o posición.', 'indicadores' => [
                    ['codigo' => 'I.M.1.1.2', 'descripcion' => 'Señala cuál conjunto tiene más, menos o la misma cantidad.'],
                ]],
                ['codigo' => 'M.1.4.3', 'descripcion' => 'Ubicar objetos y personas usando relaciones de lugar.', 'indicadores' => [
                    ['codigo' => 'I.M.1.1.3', 'descripcion' => 'Usa palabras como arriba, abajo, dentro, fuera, delante o detrás.'],
                ]],
            ],
        ]],
        'Comprensión y expresión oral y escrita' => [[
            'codigo' => 'CE.LL.1.1',
            'descripcion' => 'Comunica ideas y experiencias de forma oral y reconoce su nombre y rótulos de uso cotidiano.',
            'destrezas' => [
                ['codigo' => 'LL.1.5.1', 'descripcion' => 'Comunicar de manera oral ideas, experiencias y necesidades.', 'indicadores' => [
                    ['codigo' => 'I.LL.1.1.1', 'descripcion' => 'Cuenta una idea o una experiencia y responde una pregunta sencilla.'],
                ]],
                ['codigo' => 'LL.1.5.2', 'descripcion' => 'Reconocer su nombre y rótulos frecuentes del aula.', 'indicadores' => [
                    ['codigo' => 'I.LL.1.1.2', 'descripcion' => 'Identifica su nombre escrito y un rótulo conocido.'],
                ]],
                ['codigo' => 'EFL.1.5.4', 'descripcion' => 'Decir gustos sencillos en inglés, como I like o I don\'t like.', 'imprescindible' => false, 'indicadores' => [
                    ['codigo' => 'I.EFL.1.5.1', 'descripcion' => 'Usa una frase corta en inglés para decir si algo le gusta.'],
                ]],
            ],
        ]],
        'Comprensión y expresión artística' => [[
            'codigo' => 'CE.ECA.1.1',
            'descripcion' => 'Explora sonidos, el dibujo y el juego dramático para expresar ideas y emociones.',
            'destrezas' => [
                ['codigo' => 'ECA.1.6.1', 'descripcion' => 'Explorar sonidos de la voz, del cuerpo y de objetos, y usarlos en la creación musical.', 'indicadores' => [
                    ['codigo' => 'I.ECA.1.1.1', 'descripcion' => 'Produce sonidos y los combina en un juego musical libre o dirigido.'],
                ]],
                ['codigo' => 'ECA.1.6.2', 'descripcion' => 'Usar el dibujo para expresar su historia personal.', 'indicadores' => [
                    ['codigo' => 'I.ECA.1.1.2', 'descripcion' => 'Dibuja una vivencia y la explica con sus palabras.'],
                ]],
                ['codigo' => 'ECA.1.6.3', 'descripcion' => 'Participar en juegos dramáticos poniéndose en el lugar del otro.', 'indicadores' => [
                    ['codigo' => 'I.ECA.1.1.3', 'descripcion' => 'Representa un personaje y escucha el papel de sus compañeros.'],
                ]],
            ],
        ]],
        'Expresión corporal' => [[
            'codigo' => 'CE.EF.1.1',
            'descripcion' => 'Expresa emociones y mensajes con el cuerpo y cuida de sí y de los otros al moverse.',
            'destrezas' => [
                ['codigo' => 'EF.1.7.1', 'descripcion' => 'Reconocer emociones y comunicarlas con el cuerpo.', 'indicadores' => [
                    ['codigo' => 'I.EF.1.1.1', 'descripcion' => 'Muestra con gestos alegría, tristeza, enojo u otra emoción.'],
                ]],
                ['codigo' => 'EF.1.7.2', 'descripcion' => 'Usar gestos, posturas y ritmos para comunicar un mensaje.', 'indicadores' => [
                    ['codigo' => 'I.EF.1.1.2', 'descripcion' => 'Combina movimiento lento, rápido, suave o fuerte para expresarse.'],
                ]],
                ['codigo' => 'EF.1.7.7', 'descripcion' => 'Cuidar de sí y de los otros durante las prácticas corporales.', 'indicadores' => [
                    ['codigo' => 'I.EF.1.1.3', 'descripcion' => 'Se mueve sin empujar y respeta el espacio de los demás.'],
                ]],
            ],
        ]],
    ],
];
