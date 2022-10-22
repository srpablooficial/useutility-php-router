<?php

class Users
{

    public function GET($param)
    {
        if (!isset($param['id'])) {
            return false;
        }

        print_r("do call with param");

    }

    public function POST($params)
    {
        echo "POST_Users";
    }

    public function PUT($params)
    {
        echo "PUT_Users";
    }

    public function DELETE($params)
    {
        echo "DELETE_Users";
    }

}