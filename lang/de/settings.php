<?php

return [

    'permission' => 'Inhaltsverzeichnis-Einstellungen verwalten',

    'groups' => [
        'defaults' => [
            'title' => 'Inhaltsverzeichnis',
            'description' => 'Womit ein {{ toc }} arbeitet, wenn die Vorlage nichts anderes sagt. Jeder Wert lässt sich am Tag einzeln überschreiben; was hier steht, gilt für alle übrigen Stellen.',
        ],
    ],

    'fields' => [
        'field' => [
            'label' => 'Feld',
            'description' => 'Aus welchem Feld der Eintrag das Tag den Text nimmt, solange kein „field“- oder „content“-Parameter dabeisteht.',
        ],
        'from' => [
            'label' => 'Erste Ebene',
            'description' => 'Ab welcher Überschriftenebene das Verzeichnis beginnt. Höhere Überschriften darüber tauchen nicht auf.',
        ],
        'depth' => [
            'label' => 'Ebenen',
            'description' => 'Wie viele Ebenen das Verzeichnis umfasst, gezählt ab der ersten. Eine gesetzte Endebene schlägt diesen Wert.',
        ],
        'to' => [
            'label' => 'Letzte Ebene',
            'description' => 'Die Ebene, bei der das Verzeichnis endet, absolut gezählt. Gesetzt, gilt sie statt der Anzahl der Ebenen. Leer heißt: die Anzahl entscheidet.',
        ],
        'flat' => [
            'label' => 'Flache Liste',
            'description' => 'An: alle Überschriften liegen auf einer Ebene nebeneinander. Aus: sie stehen ineinander, wie im Text.',
        ],
    ],

];
