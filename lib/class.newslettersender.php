<?php

class newslettersender
{
    public function getName()
    {
        $about = $this->about();

        return $about['name'];
    }

    public function getHandle()
    {
        $about = $this->about();

        return Lang::createHandle($this->getName(), 255, '-');
    }
}
