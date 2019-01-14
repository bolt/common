<?php

declare(strict_types=1);

namespace Bolt\Common\Exception;

use Seld\JsonLint\ParsingException as JsonParseException;

class ParseException extends \RuntimeException
{
    /** @var int */
    private $parsedLine;
    /** @var string|null */
    private $snippet;
    /** @var string */
    private $rawMessage;

    /**
     * Constructor.
     *
     * @param string      $message    The error message
     * @param int         $parsedLine The line where the error occurred
     * @param string|null $snippet    The snippet of code near the problem
     * @param \Throwable  $previous   The previous exception
     */
    public function __construct(string $message, int $parsedLine = -1, ?string $snippet = null, int $code = 0, ?\Throwable $previous = null)
    {
        $this->parsedLine = $parsedLine;
        $this->snippet = $snippet;
        $this->rawMessage = $message;

        $this->updateRepr();

        parent::__construct($this->message, $code, $previous);
    }

    /**
     * Casts JsonLint ParseException to ours.
     */
    public static function castFromJson(JsonParseException $exception): self
    {
        $details = $exception->getDetails();
        $message = $exception->getMessage();
        $line = $details['line'] ?? -1;
        $snippet = null;

        if (preg_match("/^Parse error on line (\\d+):\n(.+)\n.+\n(.+)$/", $message, $matches)) {
            $line = (int) $matches[1];
            $snippet = $matches[2];
            $message = $matches[3];
        }

        $trailingComma = false;
        $pos = mb_strpos($message, ' - It appears you have an extra trailing comma');
        if ($pos > 0) {
            $message = mb_substr($message, 0, $pos);
            $trailingComma = true;
        }

        if (mb_strpos($message, 'Expected') === 0 && $trailingComma) {
            $message = 'It appears you have an extra trailing comma';
        }

        $message = 'JSON parsing failed: ' . $message;

        return new static($message, $line, $snippet, JSON_ERROR_SYNTAX);
    }

    /**
     * Gets the message without line number and snippet.
     */
    public function getRawMessage(): string
    {
        return $this->rawMessage;
    }

    /**
     * Sets the message.
     *
     * Don't include line number and snippet in this as they will be merged separately.
     *
     * @param string $rawMessage
     */
    public function setRawMessage(string $rawMessage): void
    {
        $this->rawMessage = $rawMessage;

        $this->updateRepr();
    }

    /**
     * Gets the line where the error occurred.
     *
     * @return int The file line
     */
    public function getParsedLine(): int
    {
        return $this->parsedLine;
    }

    /**
     * Sets the line where the error occurred.
     *
     * @param int $parsedLine The file line
     */
    public function setParsedLine($parsedLine): void
    {
        $this->parsedLine = $parsedLine;

        $this->updateRepr();
    }

    /**
     * Gets the snippet of code near the error.
     *
     * @return string The snippet of code
     */
    public function getSnippet(): string
    {
        return $this->snippet;
    }

    /**
     * Sets the snippet of code near the error.
     *
     * @param string $snippet The code snippet
     */
    public function setSnippet($snippet): void
    {
        $this->snippet = $snippet;

        $this->updateRepr();
    }

    /**
     * Sets the exception message by joining the raw message, parsed line, and snippet.
     */
    private function updateRepr(): void
    {
        $this->message = $this->rawMessage;

        $dot = false;
        if (mb_substr($this->message, -1) === '.') {
            $this->message = mb_substr($this->message, 0, -1);
            $dot = true;
        }

        if ($this->parsedLine >= 0) {
            $this->message .= sprintf(' at line %d', $this->parsedLine);
        }

        if ($this->snippet) {
            $this->message .= sprintf(' (near "%s")', $this->snippet);
        }

        if ($dot) {
            $this->message .= '.';
        }
    }
}
