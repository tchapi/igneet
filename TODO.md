## TODO ##

 * signup : avatar
    + chgt d'avatar

 * login : un peu de style plus messages d'erreurs

 * pouvoir desactiver le mode edit lorsqu'on est owner ou participant
 
 * log des modifs sur les projets

 * tinyMCE ou markdown dans les about ?? --> voir projet staré dans github

 * wiki

 * images !!! dans assetic
  + Sg fault in generate ?

 * mettre les css dans les bons bundles!

 * background image pour les projets ?

V2 

 * listes : reconnaitre les urls en mettre un lien pour y aller directement a coté ;)


## Call benjamin

 - tous les meta sont publics pour les gens qui y sont et pas pour les autres

 public :
  meta
  projects: page info
            shoutbox quand les posts outside

au niveau meta, la timeline ne ressort que les trucs publics des proejts en dessous

lien vers des droplr avec apercu auto


IDEA :

une idée est publique dans la meta
 - description textuelle principale
  deux champs : Concept et Connaissance
 - discussion
  tagger 'concept' ou 'connaissance' dans le fil de discussion

 * séparer Background / Concept


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
