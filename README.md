# Gokeep Tracking (Magento)
### Versão
0.0.1

### Sobre
Módulo desenvolvido para lojas magento para obter informações dos usuários que navegam na loja

### Instalação
```sh
$ cd caminho/projeto
$ mkdir app/code/community/Gokeep
$ git submodule add git@github.com:mferracioli/magento-gokeep.git app/code/community/Gokeep/Tracking
$ cp app/code/community/Gokeep/Tracking/Gokeep_Tracking.xml app/etc/modules/Gokeep_Tracking.xml
$ mkdir app/design/frontend/default/YOUR_THEME/layout/gokeep
$ cp app/code/community/Gokeep/Tracking/tracking.xml app/design/frontend/default/YOUR_THEME/layout/gokeep/tracking.xml
$ mkdir app/design/frontend/default/YOUR_THEME/template/gokeep
$ cp app/code/community/Gokeep/Tracking/tracking-default.phtml app/design/frontend/default/YOUR_THEME/template/gokeep
$ cp app/code/community/Gokeep/Tracking/tracking-checkout.phtml app/design/frontend/default/YOUR_THEME/template/gokeep
```