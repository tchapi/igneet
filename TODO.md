## TODO ##

 * sign up : chosen 2 + style + cas d'erreurs + taille des champs input variables
 * login : un peu de style plus messages d'erreurs

 * Styles des notices générale (notamment le margin en haut qui est dégueu)

 * styles des assert ?

 * listes : reconnaitre les urls en mettre un lien pour y aller directement a coté ;)

 * pouvoir desactiver le mode edit lorsqu'on est owner ou participant

 * removeParticipant - removeOwner
 
V2
 
 * log des modifs sur les projets

 * Sauts à la ligne dans les about + tinyMCE ?


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
