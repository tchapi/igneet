## TODO ##

 * noms des labels dans signup et new project
    __ helpers pour les champs


 * IDEA
   --> transform into project : tout les commentaires de l'idée sont réassociés avec une marque <hr> dans la timeline pour séparer quand c'est devenu un projet
  --> mettre 'from idea' dans le projet
  fetch commence a me saouler !!!!

 * signup : avatar OK OK
    + chgt d'avatar -----> TODO Mais pas simple

 * Créer un nouveau projet OK OK

 * Afficher le meta dans les infos d'un projet
 * Add a participant : faire le 'user chooser'

 * log des modifs sur les projets

 * login et signup : focus sur le champ lors du chargement de la page

 * delete a project / idea / user etc 
 
 * Commentaires
 BaseComment ::
   \_ WikiPageComment OK OK OK
   \_ ListComment OK OK OK
   \_ ResourceComment
   \_ ProjectComment ( + Meta?) = shoutbox-like

 * wiki : 
   - parenting

 * images !!! dans assetic
  + Sg fault in generate ?

 * mettre les css dans les bons bundles!

 * background image pour les projets ?

 * Ajax pour new item, new list, new wiki page avec input a la place du 'New item' ?

V2 

 * listes : reconnaitre les urls en mettre un lien pour y aller directement a coté ;)
 * lien vers des droplr avec apercu auto
    --> tout ca doit se faire en JS a priori

## Call benjamin

 - tous les meta sont publics pour les gens qui y sont et pas pour les autres

 public :
  meta
  projects: page info
            shoutbox quand les posts outside

au niveau meta, la timeline ne ressort que les trucs publics des projets en dessous

Entity IDEA :

une idée est publique dans la meta
 - description textuelle principale OK OK
  deux champs : Concept et Connaissance OK OK
 - discussion
  tagger 'concept' ou 'connaissance' dans le fil de discussion

 * séparer Background / Concept OK OK


OK -- login nécessaire pour tout
OK -- un projet est privé au participants / owners
OK -- events : a degager
OK -- timeline : tu peux pas filtrer


# CHECK WORKFLOW
 authenticated ?
  false :> home du projet (info ou timeline)

 user owning or participant ?
  false :> home ou project

 check if object is in project
  false :> 404

#TODOS
project/{slug}/todolist/new --> POST new TodoList or form
project/{slug}/todolist/{id}/{slug} --> todolist by Id
   -  id numérique
project/{slug}/todolists --> home, first alpha

#TIMELINE
project/{slug}/timeline/{page}
     - defaults : page:1 id numérique

#EVENTS
project/{slug}/events  ---> vue calendrier
project/{slug}/events/{year}    --> All events of the year (aaaa)
project/{slug}/events/{year}/{month}    --> All events of the month (aaaa/mm)
project/{slug}/events/{year}/{month}/{day}    --> All events of the day (aaaa/mm/dd)

project/{slug}/event/{id} --> specific event (ID)

#RESSOURCES
project/{slug}/resources --> liste de toutes les ressources
project/{slug}/resource/{id}/{slug} --> page de la ressource (page simple)

project/{slug}/resource/tag/{tag} --> page listing toutes les ressources avec le tag
 

#WIKI
# --> Markdown parser ???
project/{slug}/wiki --> home du wiki
project/{slug}/wiki/{pageSlug} --> page du wiki  # PAS DE PAGE POUR LES DOSSIERS
   -- le pageSlug est choisi par l‘utilisateur
