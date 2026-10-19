# hgabka/node-bundle

CMS-mag Symfonyhoz és Sonata Adminhoz: hierarchikus oldalfa (node-ok), nyelvenkénti oldalfordítások, verziókezelés (publikus/draft verziók), időzített publikálás, jogosultságok (ACL), és a nyelv- és slug-alapú URL-ek kiszolgálása. A Kunstmaan NodeBundle koncepcióján alapul. Az oldalak tartalmát jellemzően a [`hgabka/pagepart-bundle`](../pagepart-bundle) oldalrészei adják.

## Alapfogalmak

| Entitás | Tábla | Szerep |
|---|---|---|
| `Node` | `hg_node_nodes` | az oldalfa egy csomópontja (nested set: `lft`/`lvl`/`rgt`), nyelvfüggetlen: `ref_entity_name` (az oldal-entitás osztálya), `internal_name`, `hidden_from_nav`, `deleted` |
| `NodeTranslation` | `hg_node_node_translations` | a node egy nyelvi változata: `lang`, `online`, `title`, `slug`, `url`, `weight`, időbélyegek, a publikus verzióra mutat |
| `NodeVersion` | `hg_node_node_versions` | egy verzió (`type`: public / draft), `ref_id` + `ref_entity_name` az oldal-entitásra mutat |
| `NodeVersionLock` | | szerkesztési zár (egyszerre egy szerkesztő) |
| `QueuedNodeTranslationAction` | | időzített publikálás / levétel |
| *az alkalmazás oldal-entitásai* | pl. `app_home_pages` | a tényleges tartalom – `AbstractPage`-ből származnak |

Egy oldal = egy **page entitás** (a projektben definiálod), amit a bundle `NodeVersion`-ön keresztül köt egy `NodeTranslation`-höz.

## Telepítés

```bash
composer require hgabka/node-bundle
```

```php
// config/bundles.php
Hgabka\NodeBundle\HgabkaNodeBundle::class => ['all' => true],
```

Függőségek: `hgabka/utils-bundle`, `hgabka/pagepart-bundle`, `hgabka/seo-bundle`, `symfony-cmf/routing-bundle`. A bundle `prepend`-del beállítja a `cmf_routing`-ot (`replace_symfony_router: true`, a `router.default` prioritása 100), és a compiler pass a `router` aliast a `cmf_routing.router`-re állítja – így a `SlugRouter` az oldal-URL-eket tudja illeszteni és generálni.

Routing:

```yaml
# config/routes.yaml
hgabka_node:
    resource: '@HgabkaNodeBundle/Resources/config/routing.yaml'
```

(Útvonalak: `/cms/nodes/…`, `/cms/nodewidgets/…`, `/cms/url-replace/…`.)

A kezdeti ACL beállításhoz: `bin/console hgabka:init:acl`.

## Konfiguráció

```yaml
# config/packages/hgabka_node.yaml
hgabka_node:
    show_add_homepage: true          # főoldal hozzáadása gomb a node-fán
    user_entity_class: App\Entity\User
    publish_later_stepping: 15       # percenkénti lépésköz az időzített publikálásnál
    unpublish_later_stepping: 15
    lock:
        enabled: false               # szerkesztési zár (NodeVersionLock)
        check_interval: 15           # másodperc
        threshold: 35
    route:
        pattern:
            default: '/{url}'        # nyelvenként is megadható: {hu: '/{url}', en: '/en/{url}'}
        strategy: prefix_except_default
    pages:                           # oldaltípusok metaadatai (opcionális), az oldal osztályának nevével kulcsolva
        App\Entity\Pages\HomePage:
            name: Főoldal                # kötelező; alapból az osztály rövid neve
            is_homepage: true
            indexable: true
            structure_node: false
            icon: ~
            hidden_from_tree: ~
            search_type: ~
            allowed_children: []
```

## Oldaltípus létrehozása

```php
use Hgabka\NodeBundle\Entity\AbstractPage;
use Hgabka\NodeBundle\Entity\HomePageInterface;

#[ORM\Entity]
#[ORM\Table(name: 'app_home_pages')]
class HomePage extends AbstractPage implements HasPageTemplateInterface, HomePageInterface
{
    public function getDefaultAdminType(): string
    {
        return HomePageAdminType::class;        // az oldal szerkesztő űrlapja
    }

    public function getPossibleChildTypes(): array
    {
        return [
            ['name' => 'Tartalmi oldal', 'class' => ContentPage::class],
            ['name' => 'Link', 'class' => LinkPage::class],
        ];
    }
}
```

Az `AbstractPage` adja az `id`, `title`, `pageTitle` mezőket; kötelező interfészek: `PageInterface` → `HasNodeInterface` (`getTitle`, `getParent`, `getDefaultAdminType`, `getPossibleChildTypes`, `isStructureNode`). A főoldal `HomePageInterface`-t valósít meg.

További alap- és jelölő interfészek / ősök:

- `AbstractFolderPage` – csak szerkezeti (mappa) oldal, nincs saját URL-tartalma (`isStructureNode()`)
- `Pages\LinkPage` – átirányító oldal külső vagy belső URL-re (`remoteUrl`, `opensInNewWindow`)
- `AbstractControllerAction` + `Controller\SlugActionInterface::getControllerAction()` – az oldalt egy tetszőleges controller action rendereli
- `HideFromNodeTreeInterface` – nem jelenik meg a node-fában; `HiddenFromNavInterface` – nem kerül a navigációba

## Kiszolgálás (slug routing)

A `Router\SlugRouter` a `/{url}` mintára (és nyelv-prefixre) illeszt, majd a `Controller\SlugController::slug()` megkeresi a `NodeTranslation`-t, ellenőrzi a jogosultságot (`SlugSecurityEvent`), betölti a verziót (előnézeti módban a `version` paraméter egy `NodeVersion` id-t jelöl), és az oldal `service()` metódusát hívja (`PageInterface::service`), vagy a `SlugActionInterface` szerinti controller actiont. Eseményei: `PRE_SLUG_ACTION`, `POST_SLUG_ACTION`, `SLUG_SECURITY`.

Az URL-ek a node-ok slug-jaiból épülnek; módosításuk után: `bin/console hgabka:nodes:updateurls`. A `Helper\URLHelper`, `Form\Type\URLChooserType` link-választóhoz, a `replace_url` Twig szűrő (`Twig\UrlReplaceTwigExtension`) a szövegekben lévő belső hivatkozásokat cseréli valódi URL-re: `[NT123]` vagy `[hu:NT123]` (node translation id), `[M123]` (média).

## Twig

| Függvény | Leírás |
|---|---|
| `get_node_for(page)`, `get_node_translation_for(page)`, `get_page_by_node_translation()` | node / fordítás / oldal lekérése |
| `get_node_by_internal_name()`, `get_url_by_internal_name()`, `get_path_by_internal_name()` | internal_name alapján |
| `get_url_by_node()`, `get_path_by_node()`, `get_url_by_node_translation()`, `get_path_by_node_translation()`, `get_url_by_page()`, `get_path_by_page()` | URL-ek |
| `get_node_menu(locale, node, includeOffline, includeHiddenFromNav)` | navigáció (`Helper\NodeMenu`: `getTopNodes`, `getChildren`, `getSiblings`, `getBreadCrumb`, `getCurrent`, …) |
| `get_children_by_node_id()`, `get_children_by_root_node()`, `get_node_trans_by_node_id()` | gyerekek |
| `get_page_title()`, `get_tree_path_for_page()`, `get_tree_path_for_node()` | címek, útvonal |
| `is_structure_node()`, `get_possible_child_types()`, `get_homepage_types()` | admin segédek |

## Események

A `Event\Events` konstansai (`hgabka_node.*`): `ADD_NODE`, `PRE_PUBLISH`/`POST_PUBLISH`, `PRE_UNPUBLISH`/`POST_UNPUBLISH`, `PRE_DELETE`/`POST_DELETE`, `PRE_PERSIST`/`POST_PERSIST`, `REVERT`, `ADAPT_FORM`, `CREATE_PUBLIC_VERSION`, `CREATE_DRAFT_VERSION`, `COPY_PAGE_TRANSLATION`, `RECOPY_PAGE_TRANSLATION`, `ADD_EMPTY_PAGE_TRANSLATION`, `CONFIGURE_ACTION_MENU`, `CONFIGURE_SUB_ACTION_MENU`, `SLUG_SECURITY`, `PRE_SLUG_ACTION`, `POST_SLUG_ACTION`. Az eseményosztályok: `NodeEvent`, `SlugEvent`, `SlugSecurityEvent`, `AdaptFormEvent`, stb.

## Parancsok

| Parancs | Leírás |
|---|---|
| `hgabka:nodes:cron` | az időzített műveletek (publikálás/levétel) futtatása – cron-ból futtasd |
| `hgabka:nodes:updateurls` | az összes fordítás URL-jének újraszámolása |
| `hgabka:nodes:fix-timestamps` | a node fordítások időbélyegeinek javítása |
| `hgabka:nodes:convertsequencenumbertoweight` | régi sequence number → `weight` átalakítás |
| `hgabka:init:acl` | alap ACL inicializálás |

## Admin

`Admin\NodeAdmin` (Sonata), `AdminList\NodeAdminListConfigurator` + `NodeSearchFilterType`, `Controller\NodeAdminController` (publikálás, draft, verzió-visszaállítás, másolás, törlés), `Helper\Menu\PageMenuAdaptor` (az oldalfa az admin menüben), `Helper\FormWidgets\PermissionsFormWidget` (oldalankénti jogosultságok).
