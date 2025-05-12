#UI
#ui tutorials: http://shiny.rstudio.com/tutorial/lesson2/
#radioButtons: https://shiny.rstudio.com/reference/shiny/latest/radioButtons.html
#shiny themes: https://rstudio.github.io/shinythemes/
library(shiny)
library(shinyjs)
library(shinydashboard)
library(mapview)
library(shinythemes)


shinyUI(fluidPage(
  theme = shinytheme("cyborg"), 
  #theme"darkly" makes the font in a popup invisible

  tags$head(
    tags$style(HTML("
                    @import url('//fonts.googleapis.com/css?family=Lobster|Cabin:400,700');
                    
                    h1 {
                    font-family: 'Lobster', cursive;
                    font-weight: 500;
                    line-height: 1.1;
                    color: #00ccff;   #ffffff
                    }
                    
                    "))
    ),
  
  headerPanel("Bangkok Population Synthesis & Visualization"),
  #titlePanel(""), #48ca3b, navbarPage(title = 'Nawat')
  
  tabsetPanel("tab",
    
    tabPanel("Visualization",
        sidebarLayout(
      
          sidebarPanel(
            fluidRow(sliderInput("divide", "The population divided by :",
                                  min = 3, 
                                  max = 22,
                                  value = 12)
            ),
            fluidRow(radioButtons("gender","Gender", c("Both" = "both",
                                                        "Male" = "male",
                                                        "Female" = "female"))
      
            ),
            fluidRow(radioButtons("style","Style", c( "Default"= "default",
                                                      "K-Means Clustering"      = "kmeans",
                                                      "Pam Clustering"          = "pam",
                                                      "Hierarchical Clustering" = "hie",
                                                      "Density Map" = "den"))
            ),
            fluidRow(
              column(2),
              column(10,
              sliderInput("density_degree", "Choose the smoothed intensity:",
                                     min = 100, 
                                     max = 1000,
                                     value = 1000,
                                     step = 100)
                
              ),
              column(2),
              column(10,
              radioButtons("density_color","Choose the color", c( "Red" = "red",
                                                                  "Blue" = "blue",
                                                                  "Gold" = "gold"))
              )
            ),
            fluidRow(
              downloadButton("downloadData", "Download a 'Dots' output")
              
            ),
            width = 2
          ),
      
          mainPanel(
            useShinyjs(),
            div(
              id="loading",
              style= "margin-top:25%;", #This is what I've search for for 2 hrs.
              fluidRow(
                align = "center",
                img(src = "spinner.gif", height="200", width="200")
              )
            ),
            div(
              id="show",
              leafletOutput("bkkmapplot", width = "100%", height = 700 ),
              mapview:::plainViewOutput("test")
            )
          )
            
        )
        
    ), 
    
    tabPanel("Bar Graph",
      sidebarLayout(   
         sidebarPanel(
           fluidRow(
             downloadButton("downloadData2", "Download a 'Bar Graph' output")
             
           ),
           width = 2
          ),
           mainPanel(
             useShinyjs(),
             div(
               id="loading2",
               style= "margin-top:25%;", #This is what I've search for for 2 hrs.
               fluidRow(
                 align = "center",
                 img(src = "spinner.gif", height="200", width="200")
               )
             ),
             div(
               id="show2",
               plotOutput("bargraph", width = "60%", height = 700)
             )
           )
      )         
    ),
    tabPanel("Instruction", 
        tabsetPanel("lang",
          tabPanel("English", tags$h3("The instruction is coming soon.") ), tabPanel("Francais", tags$h3("Le conseil d'utilisation va bientot arriver.")  )
        )            
    )
    
  )
    
))




