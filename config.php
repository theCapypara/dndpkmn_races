<?php
declare(strict_types=1);


class Config
{
    const RAISE_EXCEPTION = Exception::class;

    public static function isDevmode(): bool
    {
        return (bool) self::_get('DEVMODE', false);
    }

    public static function getAuthToken(): string
    {
        return self::_get('AUTH_TOKEN', self::RAISE_EXCEPTION);
    }

    public static function getDbUri(): string
    {
        return self::_get('MONGO_DB_URI', self::RAISE_EXCEPTION);
    }

    public static function getDbName(): string
    {
        return self::_get('MONGO_DB_NAME', 'dndpkmn');
    }

    private static function _get($key, $default=null)
    {
        if ($default == self::RAISE_EXCEPTION && !array_key_exists($key, $_SERVER) ) {
            throw new Exception('Required config $_SERVER key ' . $key . ' missing.');
        }
        return array_key_exists($key, $_SERVER) ? $_SERVER[$key] : $default;
    }
}

function normalizeName($name)
{
    return str_replace("♂", "w",
        str_replace("♀", "w",
            str_replace(":", "",
                str_replace("'", '-',
                    str_replace("\n", '-',
                        str_replace(' ', '-',
                            str_replace('.', '', strtolower($name))
                        )
                    )
                )
            )
        )
    );
}
