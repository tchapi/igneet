Igneet
===

A tool for communities.


## Credits and license

igneet is developped by [tchapi](https://github.com/tchapi). It is copyrighted material.

© 2014 - tchapi

> For a list of open-source projects user herein, see [the credits](http://igneet.com/app/credits).

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


