<?php

namespace Goldnead\StatamicToc\Support;

use Goldnead\BrandContext\Contracts\ProvidesSettings;
use Goldnead\StatamicToc\Tags\Toc;

/**
 * Die Vorgaben des `{{ toc }}`-Tags, änderbar ohne Dateizugriff.
 *
 * Diese Klasse ist **nur** die Feldliste. Bildschirm, Formular, Validierung,
 * Speicher und Rechteprüfung stellt `goldnead/statamic-brand-context`
 * (siehe {@see ProvidesSettings}). Kein eigener Controller, keine eigene Seite,
 * keine eigene Route, keine `toc_settings`-Tabelle.
 *
 * **Warum das hier steht.** Es ist die ganze Config-Datei. Jeder der fünf Werte
 * ist eine Seitenvorgabe, jeder ist am Tag einzeln überschreibbar, und keiner
 * hat eine technische Nebenwirkung: `field` heißt auf jeder Seite anders,
 * `from`/`to`/`depth` sind eine redaktionelle Entscheidung darüber, wie tief
 * ein Inhaltsverzeichnis geht. Wer das Addon kauft, sucht als Erstes danach.
 *
 * **Nur mit `statamic-brand-context`.** Das Paket ist keine Voraussetzung
 * dieses Addons — es verlangt Statamic 6, während dieses Addon auch auf
 * Statamic 5 läuft. Ohne den Nachbarn meldet sich hier nichts an und alles
 * bleibt, wie es war: die Werte stehen dann in `config/statamic-toc.php` und
 * werden von dort gelesen.
 */
class Settings implements ProvidesSettings
{
    /**
     * Der Handle des Addons, nicht der Name der Config-Datei. Er steht in
     * `brand_settings.namespace` auf jeder Zeile; ein Umbenennen verwaist jede
     * gespeicherte Überschreibung.
     */
    public static function settingsNamespace(): string
    {
        return 'toc';
    }

    /**
     * Die Config-Wurzel, der ungesetzte Werte weiter folgen. Sie heißt anders
     * als der Namensraum: gelesen wird `config('statamic-toc.…')`.
     */
    public static function settingsConfigPath(): string
    {
        return 'statamic-toc';
    }

    /**
     * Das Recht, das diesen Abschnitt bewacht. Neu vergeben — dieses Addon
     * hatte bisher überhaupt keine Rechte.
     */
    public static function settingsPermission(): string
    {
        return 'manage toc settings';
    }

    /**
     * Die fünf Felder, in der Reihenfolge des Bildschirms.
     *
     * Alle fünf werden beim Rendern des Tags gelesen
     * ({@see Toc::config()}), keines beim Booten, in
     * einer Route oder bei einer Nav-Registrierung. Das ist die Bedingung:
     * `SettingsManager::apply()` läuft aus `app->booted()`, und ein Schalter,
     * der erst beim nächsten Deploy wirkt, wäre eine Lüge im Formular.
     *
     * @return array<int, array{title: string, description: string, fields: array<int, array<string, mixed>>}>
     */
    public static function settingsGroups(): array
    {
        return [
            [
                'title' => __('statamic-toc::settings.groups.defaults.title'),
                'description' => __('statamic-toc::settings.groups.defaults.description'),
                'fields' => [
                    static::field('field', 'string'),
                    static::field('from', 'select', ['options' => static::levels()]),
                    static::field('depth', 'integer', ['min' => 1]),
                    static::field('to', 'select', ['options' => static::levels(), 'nullable' => true]),
                    static::field('flat', 'boolean'),
                ],
            ],
        ];
    }

    /**
     * Die sechs Überschriftenebenen, die HTML hat.
     *
     * Als Auswahl und nicht als Freitext: `h7` gibt es nicht, und wer es
     * tippt, merkt es sonst erst an einem leeren Inhaltsverzeichnis auf der
     * Seite.
     *
     * @return array<string, string>
     */
    protected static function levels(): array
    {
        return array_combine(
            $levels = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'],
            array_map(strtoupper(...), $levels)
        );
    }

    /**
     * Ein Feld, mit Beschriftung und Hilfetext aus den Sprachdateien.
     *
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    protected static function field(string $key, string $type, array $extra = []): array
    {
        return array_merge([
            'key' => $key,
            'type' => $type,
            'label' => __("statamic-toc::settings.fields.{$key}.label"),
            'description' => __("statamic-toc::settings.fields.{$key}.description"),
            'nullable' => false,
        ], $extra);
    }
}
