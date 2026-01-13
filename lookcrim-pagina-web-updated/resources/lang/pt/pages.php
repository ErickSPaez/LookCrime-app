<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Pagination Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used by the paginator library to build
    | the simple pagination links. You are free to change them to anything
    | you want to customize your views to better match your application.
    |
    */

    /*
     * Geral
     */

    'global_author' => 'Equipa LookCrim',
    'title_pt'=>'Título Português',
    'title_en'=>'Título Inglês',
    'title' => 'Título',
    'content_pt'=>'Conteúdo Português',
    'content_en'=>'Conteúdo Inglês',
    'content' => 'Conteúdo',
    'event_pt'=>'Evento Português Nº',
    'event_en'=>'Evento Inglês Nº',
    'highlight_pt'=>'Destaque Português Nº',
    'highlight_en'=>'Destaque Inglês Nº',
    'center-text_pt'=>'Texto Central Português Nº',
    'center-text_en'=>'Texto Central Inglês Nº',
    'private' => 'Privada',
    'highlight' => 'Destacada',
    'empty-page' => 'Ainda não existem registos para mostrar.',
    'empty-page-cta' => 'Crie o seu primeiro registo usando o botão abaixo.',


    'notification' => 'Notificação',




    /*
     * Error Pages
     */

    'error404' => 'Erro 404',
    'error404-message' => 'Pedimos desculpa, a página que estava à procura não pode ser encontrada.',


    /*
     * Admin - Users Management
     */

    'management_title' => 'Gestão de Utilizadores',
    'management_subtitle' => '',
    'name' => 'Nome',
    'verified_account' => 'Conta Verificada',
    'yes' => 'Sim',
    'no' => 'Não',
    'date-created' => 'Data de Criação',
    'type' => 'Tipo de Utilizador',
    'account-state' => 'Estado da Conta',
    'action' => 'Editar',

    'common-user' => 'Comum',
    'admin' => 'Admnistrador',
    'available' => 'Disponível',
    'banned' => 'Banido',

    /*
     * Admin - Users Registers Management
     */

    'registrations-management_title' => 'Gestão dos Pedidos de Registo dos Utilizadores',
    'email-verified-at' => 'E-Mail Verificado A',
    'current-token' => 'Token Atual',
    'email-sent' => 'Enviado',
    'email-not-sent' => 'Não enviado',

    //------

    /*
     * Homepage
     */

     'events' => 'Eventos',
     'highlights' => 'Destaques',
     'publications' => 'Publicações',

    // Landing (public)
    'landing_private_platform' => 'Plataforma privada do Observatório Permanente de Violência e Crime (LookCrim).',

    'edit-homepage-title' => 'Editar Os Conteúdos da Homepage',

    /******/

    'dear-administrator' => 'Caro Administador,<br/><br/>',

    'the-user' => 'O utilizador ',

    'with-email' => ' com o e-mail ',

    'user-institution' => ', pertencente à instituição ',

    'continuous-text' => ', registou-se no website LookCrim, deixando a seguinte observação:<br/><br/>',

    'validate-user' => '<br/><br/>Para validar o seu registo, dirija-se à página de Gestão de Pedidos de Registo dos Utilizadores, ou ',

    'welcome' => 'Bem-vindo ',

    'continuous-text2' => ',<br/></br>Para concluir o seu registo é necessário aceder a este ',

    'continuous-text3' => ' para completar as suas informações.',

    'the-team' => '<br/><br/>A equipa,<br/>LookCrim.'
,
    // Categorias para publicações (mapa/formulário)
    'robo' => 'Roubo',
    'poco_iluminacion' => 'Pouca iluminação',
    'zona_insegura' => 'Zona insegura',
    'zona_transitada' => 'Zona transitada',
    'construccion' => 'Construção',
    'otro' => 'Outro'
    , 'categories' => 'Categorias'
    , 'category' => 'Categoria'
    , 'map_title' => 'Mapa de registos'
    , 'radius_km' => 'Raio (km)'
    , 'types' => 'Tipos'
    , 'select_all' => 'Seleccionar tudo'
    , 'search_in_map_view' => 'Pesquisar na vista atual do mapa (em vez do raio)'
    , 'use_my_location' => 'Usar minha localização ao abrir'
    , 'apply' => 'Aplicar'
    , 'clear' => 'Limpar'
    , 'you_are_here' => 'Você está aqui'
    , 'confirm_use_location' => 'Permitir usar a sua localização para centrar o mapa?'
    , 'searching' => 'Pesquisando...'
    , 'no_publications' => 'Nenhuma publicação para mostrar'
    , 'error_network' => 'Erro (rede)'
    , 'results_suffix' => 'resultados'
    , 'porto' => 'Porto'
    , 'braga' => 'Braga'
    , 'view_list' => 'Lista'
    , 'view_map' => 'Mapa'
    , 'view_toggle_aria' => 'Alternar entre vista de lista e mapa'
    , 'publication' => 'Publicação'
    , 'server_error' => 'Erro no servidor'
    , 'select_location' => 'Selecionar local'
    , 'select_location_mode' => 'Clique no mapa para escolher o centro'
    , 'page_settings' => 'DEFINIÇÕES DE PAPÉIS'
    , 'roles' => 'Perfis'
    , 'permissions' => 'Permissões'
    , 'name' => 'Nome'
    , 'actions' => 'Ações'
    , 'no_permissions' => 'Sem permissões'
    , 'edit' => 'Editar'
    , 'no_roles_defined' => 'Sem perfis definidos'
    , 'back' => 'Voltar'
    , 'edit_role' => 'Editar Perfil'
    , 'name_en' => 'Nome (EN)'
    , 'name_pt' => 'Nome (PT)'
    , 'save' => 'Guardar'
    , 'role_updated_successfully' => 'Perfil atualizado com sucesso'
     , 'create_role' => 'Criar Perfil'
     , 'role_created' => 'Perfil criado com sucesso'
     , 'role_deleted' => 'Perfil removido com sucesso'
     , 'delete' => 'Remover'
     , 'confirm_delete_role' => 'Tem a certeza que pretende remover este perfil?'
     , 'cannot_delete_role_in_use' => 'Não é possível remover o perfil: existem utilizadores atribuídos'
     , 'slug' => 'Slug'
     , 'create' => 'Criar'
     , 'role_name' => 'Nome do perfil'
     , 'permissions_from_role' => 'As permissões são definidas pelo perfil selecionado e não podem ser editadas por utilizador.'
];
