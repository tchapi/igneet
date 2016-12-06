Igneet
===

A tool for communities based on Symfony 2


## Code Organisation

The code base is divided in 6 bundles :

  - ###AdminBundle

    Contains all code related to the administration panel. Also contains the `version:bump` command

    **Entities** : 
      - `Announcement` - _custom repository_

  - ###GeneralBundle

    Contains all general code, shared with all the other bundles, namely stuff about Communities, Logs, Comments and Tags. Root for all public code : JS, CSS and images in `\Resources\public\`.

    Implements a Listener to have a `preexecute` functionality in all other controllers.

    **Entities** : 
      - `Community\Community` - _custom repository_
      - `Behaviour\Tag`
      - `Behaviour\Taggable`
      - `Comment\BaseComment` - _Parent class to all comments classes_, _custom repository_
      - `Comment\CommunityComment`
      - `Log\BaseLogEntry` - _Parent class to all logs classes_, _custom repository_
      - `Log\IdeaLogEntry` - _custom repository_
      - `Log\StandardProjectLogEntry` - _custom repository_
      - `Log\UserLogEntry` - _custom repository_
    
    **Services** : 
      - `LogService` - _all about logs_
      - `TextService` - _slugify function_

  - ###ProjectBundle

    Contains all code related to projects (ie. Wikipages, resources, lists, listitems).

    **Entities** : 
      - `Comment\StandardProjectComment` - _child class for StandardProjects comment_
      - `Comment\WikiPageComment` - _child class for Wikipages comment_
      - `Comment\CommonListComment` - _child class for lists comment_
      - `CommonList` - _custom repository_
      - `CommonListItem` - _custom repository_
      - `Resource`
      - `StandardProject` - _custom repository_
      - `Wiki`
      - `WikiPage` - _custom repository_

  - ###IdeaBundle

    Contains all code related to ideas.

    **Entities** : 
      - `Comment\IdeaComment` - _child class for Ideas comment_
      - `Idea` - _custom repository_

  - ###UserBundle

    Contains all code related to users (ie. OpenId, Skills), and non-authenticated navigation (ie. Invite tokens).

    This bundle comprises the main login / authentication functions.

    **Entities** : 
      - `OpenIdIdentity` - _user for singin up/in with OpenId_
      - `Skill` - _custom repository_
      - `User` - _custom repository_
      - `UserCommunity` - _custom repository_
      - `UserInviteToken`

    Implements a LanguageListener to handle session / request locales, and a OpenIdUserManager to comply with fpOpenId bundle's requirements.

  - ###StaticBundle

    This bundle manages the static site at `/`. It does not define any entity, and has a single controller to serve the various pages of the static site.

## Elastic Search index population

    php app/console fos:elastica:populate

## Unit tests

To run the unit tests with `phpunit`, you need to add some fixtures first :

    php app/console doctrine:fixtures:load --append

Then you can run the tests (you need more than 300 Mo, so the limit is set to 1Go of memory):

    phpunit -d memory_limit=1024M -c app/


## Credits and license

See Licence file

List of open-source projects used herein :

#### OpenId Symfony 2 Bundle by formapro
MIT https://github.com/formapro/FpOpenIdBundle
This bundle is under the MIT license, by https://github.com/formapro

#### ExposeTranslation Bundle by William Durand
MIT https://github.com/willdurand/BazingaExposeTranslationBundle
This bundle is under the MIT license, by William Durand (https://github.com/willdurand)

#### Bourbon & Neat by thoughtbot, inc
MIT http://bourbon.io/
Bourbon and NEAT are maintained and funded by thoughtbot, inc.

Bourbon and NEAT are Copyright © 2011-2013 thoughtbot under the MIT License. It is free software, and may be redistributed under the terms specified in the LICENSE file.

#### Alertify by Fabien Doiron
MIT http://fabien-d.github.io/alertify.js/
alertify.js is licensed under MIT, copyright © Fabien Doiron

#### Redactor.js
MIT https://github.com/html5cat/redactor-js
Redactor below version 7.6.2 is under the MIT License. See : https://github.com/html5cat/redactor-js

#### Dropzone.js by enyo
MIT https://github.com/enyo/dropzone
Dropzone is under the MIT License. See : https://github.com/enyo/dropzone

#### Font Awesome by Dave Gandy
SIL / MIT / CC BY 3.0 http://fortawesome.github.com/Font-Awesome
The Font Awesome font is licensed under the SIL Open Font License - http://scripts.sil.org/OFL.

Font Awesome CSS, LESS, and SASS files are licensed under the MIT License - http://opensource.org/licenses/mit-license.html.

The Font Awesome pictograms are licensed under the CC BY 3.0 License - http://creativecommons.org/licenses/by/3.0/

#### jCrop jQuery plugin by Kelly Hallman
MIT http://deepliquid.com/projects/Jcrop
© 2008-2010 Kelly Hallman

Free software released under MIT License

#### Nestable jQuery Plugin by David Bushell
BSD + MIT https://github.com/tchapi/Nestable
Copyright (c) 2013 David Bushell - http://dbushell.com/

Contributions by tchapi (https://github.com/tchapi) - https://github.com/tchapi/Nestable

Dual-licensed under the BSD or MIT licenses

#### Linecons UI icons
CC BY 3.0 http://designmodo.com/linecons-free
Polaris UI and Linecons is licensed under a Creative Commons Attribution 3.0 Unported (CC BY 3.0) (http://creativecommons.org/licenses/by/3.0/)

#### Markdown Bundle
MIT https://github.com/KnpLabs/KnpMarkdownBundle
Provide markdown conversion (based on Michel Fortin work).
