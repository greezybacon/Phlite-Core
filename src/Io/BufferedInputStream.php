<?php
namespace Phlite\Io;

/**
 * Buffered reader interface which tries to maintain a buffer of the 
 * underlying stream without copying or concatenating the underlying
 * stream data.
 */
class BufferedInputStream
implements InputStream, \IteratorAggregate {
    protected $stream;
    protected $buffer;
    protected $blocks = array();
    protected $length = 0;  // Length of current block
    protected $offset = 0;  // Offset of read in current block
    protected $pos = 0;     // Position in source stream
    protected $start = 0;   // Offset of current block in stream
    protected $lineno;
    
    var $blocksize = 8192;
    var $eol;
    
    function __construct($stream, $eol=false) {
        $this->eol = $eol ?: null;
        $this->ceol = strlen($eol ?: PHP_EOL);
        if (!$eol)
            ini_set('auto_detect_line_endings', true);
        if (is_resource($stream))
            $this->stream = $stream;
        else
            $this->stream = fopen($stream, 'rb+');
        stream_set_chunk_size($this->stream, $this->blocksize);
    }
    
    function read($bytes=0) {
        if ($bytes) {
            return fread($this->stream, $bytes);
        }
        else {
            $data = '';
            while ($more = fread($this->stream, $this->blocksize))
                $data .= $more;
            return $data;
        }
    }
    
    function readline() {
        $line = '';
        do {
            if ($this->eol) {
                $more = stream_get_line($this->stream, $this->blocksize, $this->eol);
            }
            else {
                $more = fgets($this->stream, $this->blocksize);
            }
                
            // Sockets may return boolean FALSE for EOF and empty string 
            // for client disconnect.
            if ($more === false && feof($this->stream))
                break;
            // Raise typed exception for disconnect
            elseif ($more === '')
                throw new Exception\ClientDisconnect();
            $line .= $more;
            $pos = ftell($this->stream);
            // Check if $blocksize bytes were read, which indicates that
            // an EOL might not have been found
            if (($pos - $this->pos) % $this->blocksize == 0) {
                // Attempt to seek back to read the EOL
                if (!$this->seek(-$this->ceol, SEEK_CUR))
                    break;
                if ($this->read($this->ceol) !== $this->eol)
                    continue;
            }
            $this->pos = $pos;
        }
        while (false);
        $this->lineno++;
        return $line;
    }
    
    function readlines() {
        while ($l = $this->readline())
            yield $this->lineno => $l;
    }
    
    function close() {
        return fclose($this->stream);
    }
    function seek($offset, $whence=SEEK_SET) {
        return fseek($this->stream, $offset, $whence);
    }
    function tell() {
        return ftell($this->stream);
    }
    
    // IteratorAggregate interface
    function getIterator() {
        return $this->readlines();
    }

    function rewind() {
        $this->seek(0);
        $this->line = 0;
    }
}