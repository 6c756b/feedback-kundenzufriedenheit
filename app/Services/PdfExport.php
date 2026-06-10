<?php

namespace App\Services;

class PdfExport
{
    public static function generate(array $survey, array $questions, array $answers): void
    {
        $fpdfPath = ROOT . '/vendor/fpdf/fpdf.php';
        if (!file_exists($fpdfPath)) {
            http_response_code(500);
            echo 'FPDF-Bibliothek nicht gefunden. Bitte vendor/fpdf/fpdf.php kopieren.';
            exit;
        }

        require_once $fpdfPath;

        $config      = require ROOT . '/config.php';
        $companyName = $config['app']['company_name'];
        $surveyLabel = $config['app']['survey_label'] ?? 'Auswertung';
        $appName     = preg_replace('/[^a-z0-9]/i', '', $config['app']['name'] ?? 'Export');

        // latin1-Kodierung für FPDF (kein Core-Font-Encoding-Problem)
        $enc = fn(string $s) => iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $s);

        // Subklasse für automatische Fußzeile ohne Extra-Seite
        $footerText = 'Export vom ' . date('d.m.Y') . ' | ' . $companyName;

        $pdf = new class($footerText, $enc) extends \FPDF {
            private string $footerText;
            private \Closure $enc;

            public function __construct(string $footerText, \Closure $enc) {
                parent::__construct('P', 'mm', 'A4');
                $this->footerText = $footerText;
                $this->enc = $enc;
            }

            public function Footer(): void {
                $this->SetY(-12);
                $this->SetFont('Helvetica', 'I', 8);
                $this->SetTextColor(115, 117, 135);
                $this->Cell(0, 5, ($this->enc)($this->footerText), 0, 0, 'C');
                $this->SetTextColor(0, 0, 0);
            }
        };

        $pdf->SetAutoPageBreak(true, 20);
        $pdf->AddPage();

        // ── Vorbereitung: Statistik berechnen ────────────────────
        $sliderValues   = [];
        $answeredCount  = 0;
        $totalCount     = count($questions);

        foreach ($questions as $q) {
            $answer = $answers[$q['id']] ?? null;
            if (!$answer || $answer['answer_type'] === null) {
                continue;
            }
            if ($answer['answer_type'] === 'not_applicable') {
                $answeredCount++;
                continue;
            }
            $answeredCount++;
            if (isset($answer['slider_value']) && $answer['slider_value'] !== null) {
                $sliderValues[] = (float)$answer['slider_value'];
            }
        }

        $avg = $sliderValues ? array_sum($sliderValues) / count($sliderValues) : null;

        // ── Header ───────────────────────────────────────────────
        $pdf->SetFont('Helvetica', 'B', 16);
        $pdf->Cell(0, 10, $enc($companyName), 0, 1, 'L');
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(115, 117, 135);
        $pdf->Cell(0, 6, $enc($surveyLabel . ' - Auswertung'), 0, 1, 'L');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(3);

        // ── Gesamtnote + Statistik (oben) ────────────────────────
        if ($avg !== null) {
            $pdf->SetFont('Helvetica', 'B', 13);
            $pdf->SetTextColor(134, 188, 36);
            $pdf->Cell(60, 8, $enc(sprintf('Ø Gesamtnote: %.2f', $avg)), 0, 0, 'L');
            $pdf->SetTextColor(115, 117, 135);
            $pdf->SetFont('Helvetica', '', 9);
            $pdf->Cell(0, 8, $enc($answeredCount . ' / ' . $totalCount . ' Fragen ausgefüllt'), 0, 1, 'R');
            $pdf->SetTextColor(0, 0, 0);
        } else {
            $pdf->SetFont('Helvetica', '', 9);
            $pdf->SetTextColor(115, 117, 135);
            $pdf->Cell(0, 6, $enc($answeredCount . ' / ' . $totalCount . ' Fragen ausgefüllt'), 0, 1, 'R');
            $pdf->SetTextColor(0, 0, 0);
        }

        $pdf->SetDrawColor(134, 188, 36);
        $pdf->SetLineWidth(0.5);
        $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->SetLineWidth(0.2);
        $pdf->Ln(5);

        // ── Metadaten ────────────────────────────────────────────
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->Cell(0, 7, $enc('Angaben zur Befragung'), 0, 1);
        $pdf->SetFont('Helvetica', '', 9);

        $meta = [
            ['Kunde',           $survey['customer_name']],
            ['Ansprechpartner', $survey['contact_person']],
            ['E-Mail',          $survey['contact_email']],
            ['Projektname',     $survey['project_name']],
            ['Bereiche',        $survey['area_names'] ?? ''],
            ['Vertrieb',        $survey['sales_user_name'] ?? ''],
            ['Projektleitung',  $survey['project_lead_name'] ?? ''],
            ['Metropolregion',  $survey['metropolregion_name'] ?? ''],
            ['Befrager',        $survey['created_by_name'] ?? ''],
            ['Code',            $survey['code']],
        ];

        if ($survey['project_id']) {
            $meta[] = ['Projekt-ID', $survey['project_id']];
        }
        if ($survey['reference_requested']) {
            if ($survey['reference_granted'] === null) {
                $refLabel = 'Angefragt (keine Rückmeldung)';
            } elseif ($survey['reference_granted']) {
                $refLabel = 'Genehmigt';
            } else {
                $refLabel = 'Abgelehnt';
            }
            $meta[] = ['Referenz', $refLabel];
        }
        if ($survey['internal_notes']) {
            $meta[] = ['Interne Anmerkungen', $survey['internal_notes']];
        }

        foreach ($meta as [$label, $value]) {
            if ($value === '' || $value === null) {
                continue;
            }
            $pdf->SetFont('Helvetica', 'B', 9);
            $pdf->Cell(45, 6, $enc($label . ':'), 0);
            $pdf->SetFont('Helvetica', '', 9);
            $pdf->MultiCell(0, 6, $enc((string)$value), 0);
        }

        $pdf->Ln(3);
        $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
        $pdf->Ln(6);

        // ── Fragen & Antworten (nach Bereich gruppiert) ──────────
        $pdf->SetDrawColor(216, 215, 215);
        $pdf->SetLineWidth(0.2);

        // Fragen nach area_name gruppieren, Reihenfolge bewahren
        $questionsByArea = [];
        $areaOrder       = [];
        foreach ($questions as $q) {
            $areaName = $q['area_name'] ?? '–';
            if (!isset($questionsByArea[$areaName])) {
                $questionsByArea[$areaName] = [];
                $areaOrder[]               = $areaName;
            }
            $questionsByArea[$areaName][] = $q;
        }

        foreach ($areaOrder as $areaName) {
            // Bereichs-Statistik berechnen
            $areaQs      = $questionsByArea[$areaName];
            $areaTotal   = count($areaQs);
            $areaAnswered = 0;
            $areaSliders  = [];
            foreach ($areaQs as $aq) {
                $ans = $answers[$aq['id']] ?? null;
                if ($ans && $ans['answer_type'] !== null) {
                    $areaAnswered++;
                    if ($ans['answer_type'] === 'slider' && $ans['slider_value'] !== null) {
                        $areaSliders[] = (float)$ans['slider_value'];
                    }
                }
            }
            $areaAvg = $areaSliders ? array_sum($areaSliders) / count($areaSliders) : null;

            $areaStat = $areaAnswered . ' / ' . $areaTotal . ' Fragen ausgefüllt';
            if ($areaAvg !== null) {
                $areaStat = sprintf('%.2f Ø Bereichsnote  |  ', $areaAvg) . $areaStat;
            }

            // Bereichs-Überschrift
            $pdf->SetFont('Helvetica', 'B', 11);
            $pdf->SetTextColor(26, 28, 40);
            $pdf->Cell(100, 8, $enc($areaName), 0, 0, 'L');
            $pdf->SetFont('Helvetica', '', 9);
            $pdf->SetTextColor(115, 117, 135);
            $pdf->Cell(0, 8, $enc($areaStat), 0, 1, 'R');
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetDrawColor(134, 188, 36);
            $pdf->SetLineWidth(0.4);
            $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
            $pdf->SetDrawColor(216, 215, 215);
            $pdf->SetLineWidth(0.2);
            $pdf->Ln(4);

            foreach ($questionsByArea[$areaName] as $q) {
                $answer = $answers[$q['id']] ?? null;

                // Blockhöhe schätzen - verhindert Seitenumbruch mitten im Block
                $blockH = 6 + 5 + 2 + 4; // Kurzform + Langform + Abstand + Padding
                if (!$answer) {
                    $blockH += 5;
                } elseif ($answer['answer_type'] === 'slider' || isset($answer['slider_value'])) {
                    $blockH += 5 + 4 + ($answer['freitext_value'] ? 10 : 0);
                } else {
                    $blockH += 8;
                }

                $pageH  = $pdf->GetPageHeight();
                $margin = 20;
                if ($pdf->GetY() + $blockH > $pageH - $margin) {
                    $pdf->AddPage();
                }

                $boxX   = 10;
                $boxY   = $pdf->GetY();
                $boxW   = 190;
                $innerX = $boxX + 4;
                $innerW = $boxW - 8;

                $pdf->SetX($innerX);
                $pdf->SetFont('Helvetica', 'B', 10);
                $pdf->MultiCell($innerW, 6, $enc($q['label_short']), 0);
                $pdf->SetX($innerX);
                $pdf->SetFont('Helvetica', '', 9);
                $pdf->SetTextColor(115, 117, 135);
                $pdf->MultiCell($innerW, 5, $enc($q['label_long']), 0);
                $pdf->SetTextColor(0, 0, 0);
                $pdf->Ln(2);

                if (!$answer || $answer['answer_type'] === null) {
                    $pdf->SetX($innerX);
                    $pdf->SetTextColor(173, 172, 171);
                    $pdf->Cell($innerW, 5, $enc('- Keine Antwort -'), 0, 1);
                    $pdf->SetTextColor(0, 0, 0);
                } elseif ($answer['answer_type'] === 'not_applicable') {
                    $pdf->SetX($innerX);
                    $pdf->SetTextColor(173, 172, 171);
                    $pdf->Cell($innerW, 5, $enc('Nicht zutreffend'), 0, 1);
                    $pdf->SetTextColor(0, 0, 0);
                } elseif ($answer['answer_type'] === 'slider' || ($answer['slider_value'] !== null)) {
                    $val     = (float)($answer['slider_value'] ?? 0);
                    $fillPct = max(0.04, (6 - $val) / 5);  // invertiert: 1=voll, 6=minimal
                    $barX    = $innerX;
                    $barY    = $pdf->GetY();
                    $barW    = 120;
                    $barH    = 5;

                    if ($val <= 1.5)     { [$r, $g, $b] = [134, 188,  36]; }
                    elseif ($val <= 2.5) { [$r, $g, $b] = [184, 212,  36]; }
                    elseif ($val <= 3.5) { [$r, $g, $b] = [230, 224,  32]; }
                    elseif ($val <= 4.5) { [$r, $g, $b] = [240, 160,  32]; }
                    elseif ($val <= 5.5) { [$r, $g, $b] = [232,  80,  32]; }
                    else                 { [$r, $g, $b] = [192,  57,  43]; }

                    $pdf->SetFillColor(216, 215, 215);
                    $pdf->Rect($barX, $barY, $barW, $barH, 'F');
                    $pdf->SetFillColor($r, $g, $b);
                    $pdf->Rect($barX, $barY, $barW * $fillPct, $barH, 'F');

                    $pdf->SetXY($barX + $barW + 3, $barY);
                    $pdf->SetFont('Helvetica', 'B', 9);
                    $pdf->Cell(20, $barH, $enc(sprintf('Note: %.1f', $val)), 0, 0, 'L');
                    $pdf->Ln($barH + 2);

                    $pdf->SetFont('Helvetica', '', 7);
                    $pdf->SetTextColor(115, 117, 135);
                    $pdf->SetX($barX);
                    $pdf->Cell($barW / 2, 4, $enc($q['slider_label_min'] ?? '1'), 0, 0, 'L');
                    $pdf->Cell($barW / 2, 4, $enc($q['slider_label_max'] ?? '6'), 0, 1, 'R');
                    $pdf->SetTextColor(0, 0, 0);

                    if (!empty($answer['freitext_value'])) {
                        $pdf->SetX($innerX);
                        $pdf->SetFont('Helvetica', 'B', 9);
                        $pdf->Cell($innerW, 5, $enc($q['freitext_context'] ?? 'Anmerkung') . ':', 0, 1);
                        $pdf->SetX($innerX);
                        $pdf->SetFont('Helvetica', '', 9);
                        $pdf->MultiCell($innerW, 5, $enc($answer['freitext_value']), 0);
                    }
                } elseif ($answer['answer_type'] === 'freitext') {
                    $pdf->SetX($innerX);
                    $pdf->SetFont('Helvetica', 'B', 9);
                    $pdf->Cell($innerW, 5, $enc('Antwort:'), 0, 1);
                    $pdf->SetX($innerX);
                    $pdf->SetFont('Helvetica', '', 9);
                    $pdf->MultiCell($innerW, 5, $enc($answer['freitext_value'] ?? ''), 0);
                }

                $pdf->Ln(3);
                $boxH = $pdf->GetY() - $boxY;
                $pdf->Rect($boxX, $boxY, $boxW, $boxH, 'D');
                $pdf->Ln(4);
            } // end foreach questions in area
            $pdf->Ln(3); // Abstand zwischen Bereichen
        } // end foreach areaOrder

        $filename = $appName . '_Auswertung_' . preg_replace('/[^a-z0-9]/i', '', $survey['code']) . '.pdf';

        // Alle Output-Buffer leeren, damit FPDF sauber senden kann
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');

        $pdf->Output('D', $filename);
        exit;
    }
}
