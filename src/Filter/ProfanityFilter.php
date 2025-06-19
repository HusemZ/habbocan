<?php

namespace App\Filter;

class ProfanityFilter
{
    private array $badWords;

    public function __construct()
    {
        $this->badWords = [
            'amk', 'aq', 'piç', 'gerizekalı', 'salak', 'aptal',
            'mal', 'yavşak', 'şerefsiz', 'göt', 'pezevenk', 'dangalak',
            'ahmak', 'hıyar', 'sik', 'oç', 'puşt', 'ibne', 'orospu', 'sürtük', 'yarrak', 'kaltak', 'sürtük', 'bok', 'siktir', 'siktir git',
            'amına', 'amın', 'amınakoyim', 'amcık', 'amcıkoydum', 'sikim', 'sikeyim', 'sikeyorsa', 'sikeyor', 'sikeyim seni', 'sikeyim amına',
            'sikeyim amcık', 'sikeyim oç', 'sikeyim piç', 'sikeyim gerizekalı', 'sikeyim salak', 'sikeyim aptal',
            'tabbo', 'cabbo', 'kabbo', 'habnet', 'o.ç', 'oç.', 'o.ç.',
        ];
    }

    /**
     * Metinde küfür/hakaret olup olmadığını kontrol eder.
     *
     * @param string $text Kontrol edilecek metin
     * @return bool Küfür içeriyorsa true, içermiyorsa false döner
     */
    public function hasProfanity(string $text): bool
    {
        $text = $this->normalizeText($text);

        foreach ($this->badWords as $word) {
            // Tam kelime eşleşmesi için kelime sınırları kontrolü yapılıyor
            if (preg_match('/\b' . preg_quote($word, '/') . '\b/ui', $text)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Metindeki küfür ve hakaretleri yıldızlarla değiştirir.
     *
     * @param string $text Temizlenecek metin
     * @return string Temizlenmiş metin
     */
    public function censorText(string $text): string
    {
        $originalText = $text;
        $text = $this->normalizeText($text);

        foreach ($this->badWords as $word) {
            $pattern = '/\b' . preg_quote($word, '/') . '\b/ui';
            $replacement = str_repeat('*', mb_strlen($word));
            $originalText = preg_replace($pattern, $replacement, $originalText);
        }

        return $originalText;
    }

    /**
     * Metni normalize eder (küçük harfe çevirir, yaygın karakter değişimlerini engeller)
     *
     * @param string $text
     * @return string
     */
    private function normalizeText(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');

        // Yaygın harf değişimlerini engelle (örn. s -> 5, a -> @)
        $replacements = [
            '0' => 'o', '1' => 'i', '3' => 'e', '4' => 'a', '5' => 's',
            '@' => 'a', '$' => 's', '+' => 't', '!' => 'i'
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }
}
