<?php

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
     * @param string      $message The error message
     * @param int         $parsedLine The line where the error occurred
     * @param string|null $snippet The snippet of code near the problem
     * @param \Throwable  $previous The previous exception
     */
    public function __construct($message, $parsedLine = -1, $snippet = null, $previous = null)
    {
        $this->parsedLine = $parsedLine;
        $this->snippet = $snippet;
        $this->rawMessage = $message;

        $this->updateRepr();

        parent::__construct($this->message, 0, $previous);
    }

    /**
     * Casts JsonLint ParseException to ours.
     *
     * @param JsonParseException $exception
     *
     * @return ParseException
     */
    public static function castFromJson(JsonParseException $exception)
    {
        $details = $exception->getDetails();
        $message = $exception->getMessage();
        $line = isset($details['line']) ? $details['line'] : -1;
        $snippet = null;

        if (preg_match("/^Parse error on line (\\d+):\n(.+)\n.+\n(.+)$/", $message, $matches)) {
            $line = (int) $matches[1];
            $snippet = $matches[2];
            $message = $matches[3];
        }

        $trailingComma = false;
        $pos = strpos($message, ' - It appears you have an extra trailing comma');
        if ($pos > 0) {
            $message = substr($message, 0, $pos);
            $trailingComma = true;
        }

        if (strpos($message, 'Expected') === 0 && $trailingComma) {
            $message = 'It appears you have an extra trailing comma';
        }

        $message = 'JSON parsing failed: ' . $message;

        return new static($message, $line, $snippet, $exception);
    }

    /**
     * Gets the line where the error occurred.
     *
     * @return int The file line
     */
    public function getParsedLine()
    {
        return $this->parsedLine;
    }

    /**
     * Sets the line where the error occurred.
     *
     * @param int $parsedLine The file line
     */
    public function setParsedLine($parsedLine)
    {
        $this->parsedLine = $parsedLine;

        $this->updateRepr();
    }

    /**
     * Gets the snippet of code near the error.
     *
     * @return string The snippet of code
     */
    public function getSnippet()
    {
        return $this->snippet;
    }

    /**
     * Sets the snippet of code near the error.
     *
     * @param string $snippet The code snippet
     */
    public function setSnippet($snippet)
    {
        $this->snippet = $snippet;

        $this->updateRepr();
    }

    private function updateRepr()
    {
        $this->message = $this->rawMessage;

        $dot = false;
        if ('.' === substr($this->message, -1)) {
            $this->message = substr($this->message, 0, -1);
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
