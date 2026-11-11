<?php
/**
 * CLI-Test für PostcolorsSpeechDetector und PostcolorsSpeechColorizer.
 * Aufruf: php tests/speech_detector_test.php
 * Nicht Teil des Release-Zips.
 */

if (PHP_SAPI !== 'cli') {
    die('CLI only.');
}

require_once __DIR__ . '/../inc/plugins/postcolors/core/SpeechDetector.php';
require_once __DIR__ . '/../inc/plugins/postcolors/core/SpeechColorizer.php';

$pass = 0;
$fail = 0;

function postcolors_test_check(string $name, string $expected, string $actual): void
{
    global $pass, $fail;
    if ($expected === $actual) {
        $pass++;
        echo "  ok   {$name}\n";
    } else {
        $fail++;
        echo "  FAIL {$name}\n       expected: {$expected}\n       actual:   {$actual}\n";
    }
}

$de = new PostcolorsSpeechDetector('„', '“');
$straight = new PostcolorsSpeechDetector('"', '"');
$single = new PostcolorsSpeechDetector("'", "'");
$guill = new PostcolorsSpeechDetector('»', '«');
$guillOut = new PostcolorsSpeechDetector('«', '»');
$en = new PostcolorsSpeechDetector('“', '”');
$deSingle = new PostcolorsSpeechDetector('‚', '‘');

echo "Detector – deutsche Anführungszeichen\n";
postcolors_test_check('01 einfache Rede',
    'Sie sagte: [„Hallo.“] Dann ging sie.',
    $de->markup('Sie sagte: „Hallo.“ Dann ging sie.'));
postcolors_test_check('02 Erzähltext zwischen zwei Reden',
    '[„Komm her“], rief er, [„sofort!“]',
    $de->markup('„Komm her“, rief er, „sofort!“'));
postcolors_test_check('03 verschachteltes Zitat mit einfachen Zeichen',
    '[„Er sagte ‚nein‘ und ging.“] Stille.',
    $de->markup('„Er sagte ‚nein‘ und ging.“ Stille.'));
postcolors_test_check('04 unbalanciert: nichts ab dem offenen Zeichen',
    '[„Ja.“] Sie lachte. „Aber dann kam niemand mehr.',
    $de->markup('„Ja.“ Sie lachte. „Aber dann kam niemand mehr.'));
postcolors_test_check('05 Romanschreibweise über zwei Absätze',
    "[„Erster Absatz der Rede.]\n\n[„Zweiter Absatz.“] Erzähltext.",
    $de->markup("„Erster Absatz der Rede.\n\n„Zweiter Absatz.“ Erzähltext."));
postcolors_test_check('06 offener Absatz ohne Fortsetzung wird nicht gefärbt',
    "„Offen geblieben.\n\nNormaler Erzähltext.",
    $de->markup("„Offen geblieben.\n\nNormaler Erzähltext."));
postcolors_test_check('07 einfacher Zeilenumbruch ist keine Absatzgrenze',
    "[„Zeile eins\nZeile zwei“] sagte sie.",
    $de->markup("„Zeile eins\nZeile zwei“ sagte sie."));
postcolors_test_check('08 Rede am Absatzanfang und -ende',
    "[„A.“]\n\n[„B.“]",
    $de->markup("„A.“\n\n„B.“"));

echo "Detector – gerade doppelte Anführungszeichen\n";
postcolors_test_check('09 gerade Quotes am Textanfang',
    '["Hi,"] he said.',
    $straight->markup('"Hi," he said.'));
postcolors_test_check('10 gerade Quotes einfach',
    'He said: ["Hello there."] Then he left.',
    $straight->markup('He said: "Hello there." Then he left.'));
postcolors_test_check('11 gerade Quotes zwei Reden',
    '["Yes,"] she said, ["of course."]',
    $straight->markup('"Yes," she said, "of course."'));
postcolors_test_check('12 Zoll-Zeichen mitten im Wort ist kein Redezeichen',
    'Ein 12"-Monitor und ["Hallo!"]',
    $straight->markup('Ein 12"-Monitor und "Hallo!"'));
postcolors_test_check('13 unbalancierte gerade Quotes färben nichts',
    'Sie rief "Warte doch und rannte weiter.',
    $straight->markup('Sie rief "Warte doch und rannte weiter.'));
postcolors_test_check('14 Rede in Klammern',
    'Er (["leise"]) ging.',
    $straight->markup('Er ("leise") ging.'));
postcolors_test_check('15 Gedankenstrich vor Rede',
    'Dann –["Halt!"]',
    $straight->markup('Dann –"Halt!"'));

echo "Detector – einfache gerade Anführungszeichen (Apostrophe)\n";
postcolors_test_check('16 Apostrophe in Peter\'s, geht\'s, don\'t',
    "Peter's Hund, geht's dir gut? I don't know.",
    $single->markup("Peter's Hund, geht's dir gut? I don't know."));
postcolors_test_check('17 Elision \'n ist kein Öffner',
    "Er hat 'n Hund und sagte ['Hallo'] dann.",
    $single->markup("Er hat 'n Hund und sagte 'Hallo' dann."));
postcolors_test_check('18 einfache Rede mit Apostroph darin',
    "She said ['it's fine.'] Then silence.",
    $single->markup("She said 'it's fine.' Then silence."));
postcolors_test_check('19 Jahreszahl \'90 ist kein Öffner',
    "In den '90ern war ['alles'] anders.",
    $single->markup("In den '90ern war 'alles' anders."));
postcolors_test_check('20 Plural-Apostroph am Wortende (the boys\' room)',
    "The boys' room was quiet.",
    $single->markup("The boys' room was quiet."));

echo "Detector – weitere Typografie\n";
postcolors_test_check('21 Guillemets »…«',
    'Sie sagte: [»Komm.«] Er kam.',
    $guill->markup('Sie sagte: »Komm.« Er kam.'));
postcolors_test_check('22 Guillemets «…»',
    'Il dit: [«Bonjour»] et partit.',
    $guillOut->markup('Il dit: «Bonjour» et partit.'));
postcolors_test_check('23 englische Anführungszeichen',
    'She said, [“Come in.”] He did.',
    $en->markup('She said, “Come in.” He did.'));
postcolors_test_check('24 deutsche einfache ‚…‘ mit geht‘s',
    'Er meinte [‚geht‘s noch?‘] und lachte.',
    $deSingle->markup('Er meinte ‚geht‘s noch?‘ und lachte.'));
postcolors_test_check('25 gemischte Typografie: nur das gewählte Paar',
    'Sie sagte "nein" und dann [„ja“].',
    $de->markup('Sie sagte "nein" und dann „ja“.'));
postcolors_test_check('26 Leerzeichen nach Öffner: kein Öffner',
    'Ein „ einzelnes Zeichen und [„Rede“].',
    $de->markup('Ein „ einzelnes Zeichen und „Rede“.'));
postcolors_test_check('27 eigenes Paar aus zwei Zeichen',
    'Er sagte [<<Hallo>>] laut.',
    (new PostcolorsSpeechDetector('<<', '>>'))->markup('Er sagte <<Hallo>> laut.'));

echo "Colorizer – HTML\n";
$colorizer = new PostcolorsSpeechColorizer('„', '“', 'pc pc-p5 pc-speech');
postcolors_test_check('30 Rede über Inline-Tag bleibt gültiges HTML',
    'Er sagte <span class="pc pc-p5 pc-speech">„Hallo </span><strong><span class="pc pc-p5 pc-speech">du</span></strong><span class="pc pc-p5 pc-speech">!“</span> Ende.',
    $colorizer->apply('Er sagte „Hallo <strong>du</strong>!“ Ende.'));
postcolors_test_check('31 Zitat-Inhalt wird nicht gefärbt',
    '<blockquote class="mycode_quote"><cite>Zitat</cite>„Alt“</blockquote><span class="pc pc-p5 pc-speech">„Neu“</span>',
    $colorizer->apply('<blockquote class="mycode_quote"><cite>Zitat</cite>„Alt“</blockquote>„Neu“'));
postcolors_test_check('32 Link-Inhalt und Attribute bleiben unangetastet',
    '<a href="x?a=„b“">„Link“</a> und <span class="pc pc-p5 pc-speech">„Rede“</span>',
    $colorizer->apply('<a href="x?a=„b“">„Link“</a> und „Rede“'));
postcolors_test_check('33 bereits gefärbte Passage wird ausgelassen',
    '<span class="pc pc-p9">„Fremd“</span> <span class="pc pc-p5 pc-speech">„Eigen“</span>',
    $colorizer->apply('<span class="pc pc-p9">„Fremd“</span> „Eigen“'));
postcolors_test_check('34 [color] (span style) wird ausgelassen',
    '<span style="color: red;" class="mycode_color">„Rot“</span>',
    $colorizer->apply('<span style="color: red;" class="mycode_color">„Rot“</span>'));
postcolors_test_check('35 Absatz über <br /><br /> (Romanschreibweise)',
    "<span class=\"pc pc-p5 pc-speech\">„Eins</span><br />\n<br />\n<span class=\"pc pc-p5 pc-speech\">„Zwei“</span> Ende.",
    $colorizer->apply("„Eins<br />\n<br />\n„Zwei“ Ende."));
$colorizerStraight = new PostcolorsSpeechColorizer('"', '"', 'pc pc-p5 pc-speech');
postcolors_test_check('36 Entities &quot; werden erkannt',
    'Er sagte <span class="pc pc-p5 pc-speech">&quot;Hallo&quot;</span>.',
    $colorizerStraight->apply('Er sagte &quot;Hallo&quot;.'));
postcolors_test_check('37 Code-Block wird ausgelassen',
    '<div class="codeblock"><div class="body" dir="ltr"><code>"x"</code></div></div><span class="pc pc-p5 pc-speech">"y"</span>',
    $colorizerStraight->apply('<div class="codeblock"><div class="body" dir="ltr"><code>"x"</code></div></div>"y"'));
postcolors_test_check('38 Text ohne Rede bleibt identisch',
    'Nur <em>Erzähltext</em> &amp; nichts weiter.',
    $colorizer->apply('Nur <em>Erzähltext</em> &amp; nichts weiter.'));

echo "\n{$pass} passed, {$fail} failed\n";
exit($fail === 0 ? 0 : 1);
