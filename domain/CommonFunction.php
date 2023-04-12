<?php

namespace app\domain;

class CommonFunction
{
    public static function checkFootBadLeague($league)
    {
        $badLeagues = [
            'ACL Indoor',
            'Afrique. Ligue Royale 2',
            'BudnesLiga LFL 5x5',
            'Derby League',
            'Division 4x4',
            'MLS 5x5',
            'Short Football 2x2',
            'Short Football 2x2 L2',
            'Short Football 3x3',
            'Short Football 3x3 L2',
            'Short Football 4x4',
            'Short Football 4x4 L2',
            'Short Football 5x5',
            'Short Football 5x5 L1',
            'Student League',
            'Student League 2',
            'USSR. 3x3. Division B',
            'USSR. 3x3. Division А',
            '5х5. Кубок России. Матч звёзд',
            'Веlarus. Short Football D1',
            'FIFA 23. World Cup',
            'США. MASL',
            'Товарищеские матчи',
            'Товарищеские матчи клубов',
            'Товарищеский турнир клубов',
            'Товарищеские матчи. Сборные',
            'Товарищеские матчи. Сборные. Женщины',
            '7x7. Лига Про',
            'Беларусь. Региональная Лига А',
            'Беларусь. Региональная лига. Запад',
            'РПЛ 6x6',
            '5x5. Премьер Лига',
            'Super League',
            'USSR. 3x3. Division A',
            '7х7. Кингс Лига',
            '8х8. ЛФЛ. Дагестан. Премьер-лига',
            'Товарищеские матчи до 20 лет. Сборные',
            'Belarus. Regional League. West',
            'Belarus. Regional League A',
            'Региональная лига. Север',
            'Short Football D1',
            'Regional League. West',
            'Regional League A'
        ];
        
        return in_array($league, $badLeagues);
    }

    public static function checkVollBadLeague($league)
    {
        $badLeagues = [
            'Amber Cup',
            'Amber Cup. Микст',
            'Capital Challenge Cup. Женщины',
            'Capital Challenge Cup',
            'Diamond Cup. Женщины',
            'Liberty League. Женщины',
            'NCAA. Женщины',
            'США. NAIA. Женщины',
            'Orange Cup',
            'Pro League. Siberia',
            'Pro League. Siberia. Женщины',
            'Ural League',
            'Ural League 2',
            'Ural League 2. Женщины',
            'Ural League 3',
            'Ural League 3. Женщины',
            'Ural League. Женщины',
            'Камерун. AVL',
            'Любительская волейбольная Лига города Минска',
            'SpikeVolleyball. International Cup. Женщины',
            'SpikeVolleyball. International Cup',
            'Ural Super League',
        ];
        
        return in_array($league, $badLeagues);
    }
}
