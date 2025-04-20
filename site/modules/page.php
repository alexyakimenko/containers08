<?php

class Page {

    private string $template;

    /**
     * @throws Exception
     */
    public function __construct(string $template) {
        if (!file_exists($template)) {
            throw new Exception("Template file not found: $template");
        }
        $this->template = $template;
    }

    public function Render(array $data): string {
        $content = file_get_contents($this->template);

        if (empty($data)) {
            return $content;
        }

        foreach ($data as $key => $value) {
            $placeholder = "{{" . $key . "}}";
            $content = str_replace($placeholder, htmlspecialchars($value), $content);
        }

        return $content;
    }
}