#SERVER

library(shiny)
library(shinyjs)
library(wordcloud)
#Misc.
library(gdata)
library(jsonlite)
library(stringr)
#spatial data handling
library(sp)
library(maptools)
library(rgdal)
#maps
library(ggplot2)
library(ggmap)
library(RgoogleMaps)
library(plotKML)
library(leaflet) #best
library(webshot)
#---density map (aj. veera)
library(spatstat)
library(raster)
library(mapview)
library(htmlwidgets)
#clustering
library(NbClust)
library(cluster)
#download
library(rmarkdown)


#==== Define server logic required to draw output ==== #input$x = get x from another file as "input"
shinyServer(function(input, output) {
  disable("divide")
  disable("gender")
  disable("style")
  disable("density_degree")
  disable("density_color")
  disable("downloadData")
  disable("downloadData2")
  
#=============== Reading & Encoding =============================
pop_bkk <- read.csv("C:/Users/Vip/Desktop/mycode/bangkok_population/population_bkk.csv", stringsAsFactors = FALSE)

#** It's an data file you've to use in your visualization
                                                                                                      #BUG 2 HOURS FOR NOT INSERTING "stringsAsFactors = FALSE"
lat_long_bkk <-  read.csv("C:/Users/Vip/Desktop/mycode/bangkok_population/district_length_of_latLong.csv", stringsAsFactors = FALSE)

  
  
#============= Creating my own colors ===========================
#main***: https://www.r-bloggers.com/palettes-in-r/
#https://rstudio.github.io/leaflet/
colors <- c("#A7A7A7","dodgerblue", "firebrick", "forestgreen", "gold")
rainbowcols <- rainbow(6)

#-----COLORS for a density map-------------------
#Set some constant variables.
dlat <- 0
dlong <- 0
DegreeLatitudeOf1KM <- 0.009039073
DegreeLongitudeOf1KM <- 0.009219323
# DegreeLatitudeOf100M <- 0.0009039073
# DegreeLongitudeOf100M <- 0.0009219323
# DegreeLatitudeOf500M <- ((DegreeLatitudeOf1KM-DegreeLatitudeOf100M)/2) + (0.05*DegreeLatitudeOf1KM)
# DegreeLongitudeOf500M <-((DegreeLongitudeOf1KM-DegreeLongitudeOf100M)/2) + (0.05*DegreeLongitudeOf1KM)

#black_to_blue_6   <- c("#000000", "#000890", "#0C3AC0", "#237CF0", "#45B3FB", "#60DDFF")
black_to_blue_9   <- c("#000000", "#000890", "#0C3AC0", "#237CF0", "#45B3FB", "#60DDFF", "#66FFFF", "#CCFFFF", "#FFFFFF")
#black_to_red_6   <- c("#000000", "#990000", "#FF0000", "#FF5050", "#FF6666", "#FF9999")
black_to_red_9   <- c("#000000", "#990000", "#FF0000", "#FF5050", "#FF6666", "#FF9999", "#FFCCCC", "#FFE6E6", "#FFFFFF")
black_to_gold_6   <- c("#000000", "#900800", "#c03A0C", "#F07C23", "#FBB345", "#FFDD60")
#black_to_green_6  <- c("#000000", "#089000", "#3AC00C", "#7CF023", "#B3FB45", "#DDFF60")
#black_to_pink_6   <- c("#000000", "#900008", "#C00C3A", "#F0237C", "#FB45B2", "#FF60DD")
#black_to_purple_6 <- c("#000000", "#080090", "#3A0CC0", "#7C23F0", "#B345FB", "#DD60FF")
#black_to_aqua_6   <- c("#000000", "#009008", "#0CC03A", "#23F07C", "#45FBB3", "#60FFDD")
#-------------------------------------------

#@@@@@@ 1.create array we must use @@@@@@@@@@
location_list <- list()
id <- "ID"
gen <- "Gender"
location <- "Location"
area <- "Area"
age <- "Age"
#others - later
                       #must be in this format "no. - no." 
age_group <- list(ag1 = "0 - 4", ag2 = "5 - 9", ag3 = "10 - 14", ag4 = "15 - 19", ag5 = "20 - 24", ag6 = "25 - 29", ag7 = "30 - 34", ag8 = "35 - 39", ag9 = "40 - 44", ag10 ="45 - 49", ag11 = "50 - 54", ag12 = "55 - 59", ag13 = "60 - 64", ag14 = "65 - 69", ag15 = "70 - 74", ag16 = "75 - 79", ag17=" 80 - 84", ag18= "85 - 89", ag19= "90 - 94", ag20= "95 - 99", ag21= "100  ขึ้นไป")
gender <- list(m="male",f="female")

for(i in 1:nrow(lat_long_bkk)){
  location_temp <- lat_long_bkk[i, "district_th"]
  #--------- age_group -------------------
  age_group_list <- list()
  for(j in 1:length(age_group)){
    age_group_list[[age_group[[j]]]] <- 0
  }
  #---------  gender -------------------
  gender_list <- list()
  for(j in 1:length(gender)){
    gender_list[[gender[[j]]]] <- 0
  }
  #====== AGAIN !!! =========
  gender_list <- sapply( gender_list, as.numeric)
  
  #---------- aggregate ----------------
  for(j in 1:length(age_group_list)){
    age_group_list[[j]] <- gender_list
  }
  location_list[[location_temp]] <- age_group_list
}

#@@@@@@@@@@@@ 2.count @@@@@@@@@@@@@@@@@@
for(i in 1:nrow(pop_bkk)){
  #------ receive inputs of "gender/location/age" here -------
  c_gender <- trimws(pop_bkk[i, gen]) #also trim here
  c_location <- trimws(pop_bkk[i, location])
  c_age <- trimws(pop_bkk[i, age])
  
  for(j in 1:length(location_list)){          #1.location level
    #must trim cuz there are white spaces in lat_long_bkk: district_th
    #use break to save runtime
    if( c_location == trimws(names(location_list)[j])){
      
      for(k in 1:length(location_list[[j]])){ #2.age level
        if(c_age == trimws(names(location_list[[j]][k]))) {
          
          #3. gender level
          if(c_gender == trimws(names(location_list[[j]][[k]][1]) )) {
            location_list[[j]][[k]][1] <- location_list[[j]][[k]][1] + 1
            #print(location_list[[j]][[k]][1])
            break #get out of 2.age level
          }
          else { #must = female
            location_list[[j]][[k]][2] <- location_list[[j]][[k]][2] + 1
            #print(location_list[[j]][[k]][2])
            break #get out of 2.age level
          }
        }
      }
      break #get out of 1.location level
    }
  }
}


#------ Print to prove input$gender is completed $ For Bar Graph-------------------------------------------------
#--Overview-------
total_male <- 0
total_female <- 0
#--for Bar Graph--------
total_pop_bar <- list()
total_male_bar <- 0
total_female_bar <- 0

for(i in 1:length(location_list)) {
  for(j in 1:length(location_list[[i]])) {
    total_male <- total_male + location_list[[i]][[j]][1]
    total_female <- total_female + location_list[[i]][[j]][2]
  }
  print(paste("total male: ", total_male, "total female: ", total_female))
}
#--------------------------------------------------------------

# &&&&&&&&&&&&&&&&&&&&& m <- Reactive &&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&
m <- reactive ({
  
  total_pop_bar <- list()
  total_male_bar <- 0
  total_female_bar <- 0
  
  print(paste("Gender: ", input$gender))
  
  #@@@@@@@@ 3.how to gen random coordinates: IN DISTRICT LEVEL @@@@@@@@@
  location_list_final <- location_list
  
    #--- 1. /input$divide & keep into location_list_final -------------------
  for(i in 1:length(location_list_final)){
    
    for(j in 1:length(location_list_final[[i]])) {
      # !!!!! CHANGE THE AMOUNT OF POINTS HERE !!!!!!
      #always round up to avoid "non-numeric" &index in for Problems
      location_list_final[[i]][[j]][1] <- round( (location_list_final[[i]][[j]][1] / input$divide) )  #ceiling /  <round is OK> !
      location_list_final[[i]][[j]][2] <- round( (location_list_final[[i]][[j]][2] / input$divide) )
          #if using round, you need this if(tt2 > 0 && location_list_final[[i]][[j]][2]==0){ location_list_final[[i]][[j]][2] <- location_list_final[[i]][[j]][2] + 1 }
      
      #print(paste("male for each <age range> in each <district> : ", location_list_final[[i]][[j]][1] ))
      #print(paste("female for each <age range> in each <district> : ", location_list_final[[i]][[j]][2] ))
      total_male_bar <- total_male_bar + location_list_final[[i]][[j]][1]
      total_female_bar <- total_female_bar + location_list_final[[i]][[j]][2]
      
    }
  }
  
  #---------- For Bar Graph --------------
  total_pop_bar[1] <- total_male_bar
  total_pop_bar[2] <- total_female_bar
  
  #---Debug non-numeric-----
  total_pop_bar <<- sapply(total_pop_bar, as.numeric)
    
  print(paste ("total_pop bar male",total_pop_bar[1]) )
  print(paste ("total_pop bar female",total_pop_bar[2]) )
  
  
  #=============== 1., 2. MIGHT BE MEREGED ===========================================
  #---2.  random coordinates according to the amount in dis_list_final  ---------------
  
  #------- 2.1 initiation --------------------
  #set.seed(1) //if no set, R'll find one by itself.
  
  #---- data frame ----
  #ans 2: http://stackoverflow.com/questions/3642535/creating-an-r-dataframe-row-by-row
  v_coords <- NULL
  #-------------------
  v_lat <- list()
  v_long <- list()
  v_info <- list()
  v_gender <- list()
  #name it 
  #column.names <- c("lat","long")
  #matrix.names <- c("v_coords")
  
  temp_index <- 1
  
  #------- 2.2 gen rand no. & keep them in vectors--------------------
  for(i in 1:length(location_list_final)){               #1. location level
    
    for(j in 1:length(location_list_final[[i]])) {       #2. age level
      
      
      if(input$gender == "both"){
      #------KEEP BOTH-----------------------------------------------------------------
        if((location_list_final[[i]][[j]][1] >0)){         #3. gender level
          
            for(k in 1:location_list_final[[i]][[j]][1]){
              v_lat[[temp_index]]  <- runif(location_list_final[[i]][[j]][1], lat_long_bkk[i,"lat_min"], lat_long_bkk[i, "lat_max"])
              v_long[[temp_index]] <- runif(location_list_final[[i]][[j]][1], lat_long_bkk[i,"long_min"], lat_long_bkk[i, "long_max"])
              v_info[[temp_index]] <-  c( paste("<b>District</b>:",trimws(names(location_list_final)[i]),"   ","<b>Gender</b>:",trimws(names(location_list_final[[j]][[k]][1])),"   ","<b>Age</b>:",trimws(names(location_list_final[[i]][j]))  ) )
              v_gender[[temp_index]] <- paste(trimws(names(location_list_final[[j]][[k]][1])))
              
              temp_index <- temp_index + 1
              #print(paste("male v_lat ->",v_lat[[temp_index]])) & others for debugging
            }
            #print("----------male from both--------------")
          
          
        }
        if(location_list_final[[i]][[j]][2] > 0){
            for(k in 1:location_list_final[[i]][[j]][2]){
              v_lat[[temp_index]]  <- runif(location_list_final[[i]][[j]][2], lat_long_bkk[i,"lat_min"], lat_long_bkk[i, "lat_max"])
              v_long[[temp_index]] <- runif(location_list_final[[i]][[j]][2], lat_long_bkk[i,"long_min"], lat_long_bkk[i, "long_max"])
              v_info[[temp_index]] <-  c( paste("<b>District</b>:",trimws(names(location_list_final)[i]),"   ","<b>Gender</b>:",trimws(names(location_list_final[[j]][[k]][2])),"   ","<b>Age</b>:",trimws(names(location_list_final[[i]][j]))  ) )
              v_gender[[temp_index]] <- paste(trimws(names(location_list_final[[j]][[k]][1])))
              
              temp_index <- temp_index + 1
            }
            #print("----------female from both--------------")
          
        }
      #------------------------------------------------------------------------
      }
      
      else if (input$gender == "male" ){
        #-------KEEP MALE---------------------------------------------------------
        if((location_list_final[[i]][[j]][1] >0)){         #3. gender level
    
          for(k in 1:location_list_final[[i]][[j]][1]){
            v_lat[[temp_index]]  <- runif(location_list_final[[i]][[j]][1], lat_long_bkk[i,"lat_min"], lat_long_bkk[i, "lat_max"])
            v_long[[temp_index]] <- runif(location_list_final[[i]][[j]][1], lat_long_bkk[i,"long_min"], lat_long_bkk[i, "long_max"])
            v_info[[temp_index]] <-  c( paste("<b>District</b>:",trimws(names(location_list_final)[i]),"   ","<b>Gender</b>:",trimws(names(location_list_final[[j]][[k]][1])),"   ","<b>Age</b>:",trimws(names(location_list_final[[i]][j]))  ) )
            v_gender[[temp_index]] <- paste(trimws(names(location_list_final[[j]][[k]][1])))
            
            temp_index <- temp_index + 1
          }
          #print("----------male--------------")
        }
        #------------------------------------------------------------------
      }
      else if(input$gender == "female"){
        #--------KEEP FEMALE-----------------------------------------------------
        if(location_list_final[[i]][[j]][2] > 0){
          for(k in 1:location_list_final[[i]][[j]][2]){
            v_lat[[temp_index]]  <- runif(location_list_final[[i]][[j]][2], lat_long_bkk[i,"lat_min"], lat_long_bkk[i, "lat_max"])
            v_long[[temp_index]] <- runif(location_list_final[[i]][[j]][2], lat_long_bkk[i,"long_min"], lat_long_bkk[i, "long_max"])
            v_info[[temp_index]] <-  c( paste("<b>District</b>:",trimws(names(location_list_final)[i]),"   ","<b>Gender</b>:",trimws(names(location_list_final[[j]][[k]][2])),"   ","<b>Age</b>:",trimws(names(location_list_final[[i]][j]))  ) )
            v_gender[[temp_index]] <- paste(trimws(names(location_list_final[[j]][[k]][1])))
            
            temp_index <- temp_index + 1
          }
          #print("----------female--------------")
          
        }
        #----------------------------------------------------------------
      }
      
    }
  }
  
  print(paste("temp_index:",temp_index))
  print(paste("v_lat length:", length(v_lat), "v_long length:", length(v_long) ))
  
  #========== 2.3 SET THEM TO BE "NUMBERIC" (BUG FOR 3 HOURS) ===========
  ### Transform() doesn't work in every case
  v_lat <- sapply(v_lat, as.numeric)
  v_long <- sapply(v_long, as.numeric)
  
  # ! ! ! ! ! This is for the scalability ! ! ! ! ! ! ! ! ! ! ! !
  #------ add all info. in a row into vcoords (data frame) -------
  for(i in 1:length(v_long)){ 
    for(j in 1:length(v_long[[i]])){
      
      # ############### CHEAT & HARDCODE FOR NITAD ################
      #---Huay Kwang 125-129 are N/A (non-numeric) -----
      #return true if "x" in "is.na(x)" is NA
      if( is.na( v_lat[[i]][[j]] ) ){
        v_lat[[i]][[j]] <- c(100.5405)
      }
      # ############################################################
      #v_info[[i]][[j]] for focusing on each attr.
      rbind(v_coords, data.frame( Information = v_info[[i]] , lat = v_lat[[i]][[j]], long = v_long[[i]][[j]], gender = v_gender[[i]] ) ) -> v_coords
    }
  }
  
  v_coords_temp <- v_coords[, c("lat","long")]
  
  #--------- 2.4 Create an obj "v_coords"  -----------------------------
  # v_coords <- matrix(c(v_lat,v_long), ncol = 2, byrow = FALSE)
  # colnames(v_coords) <- c("lat","long")
  
  #===========  FOR 2.3 CHECK THE ANSWER HERE ========================
  #ans 2: http://stackoverflow.com/questions/2288485/how-to-convert-a-data-frame-column-to-numeric-type
  #storage.mode(v_coords[i,"lat"]) <- "double" doesn't help
  for(i in 1:nrow(v_coords)){
    if(!is.numeric(v_coords[i, "lat"])) {
      print("non-numeric lat")
    }
    if(!is.numeric(v_coords[i, "long"])) {
      print("non-numeric long")
    }
  }
  # v_coords <- as.data.frame(v_coords)
  #====================================================================
  
  # #@@@@@@@@@@@@ 4. how to plot @@@@@@@@@@@@@@@@@@@@@@         #call a bkk map
  convstore_sp <- SpatialPoints(v_coords_temp, proj4string = CRS("+proj=longlat +init=epsg:4326"))
  # Create SpatialPointsDataFrame from coords and features_df$properties (a lot of info. here)

  # Create SpatialPointsDataFrame from coords and features_df$properties (a lot of info. here)        #call a bkk map
  convstore_spdf <- SpatialPointsDataFrame(v_coords_temp ,v_coords, proj4string = CRS("+proj=longlat +init=epsg:4326"))
  
  #--------- any types of visualization you want -----------------
  hide(id = "loading")
  hide(id = "loading2")
  show("show")
  show("show2")
  enable("divide")
  enable("gender")
  enable("style")
  enable("downloadData")
  enable("downloadData2")
  
  #======== Func for creating color-diff dots ===================================
  #red
  female_dots <- function(m){
    m <- leaflet()
    m <- addTiles(m)
    m <- addCircleMarkers(m, data=convstore_spdf, popup=convstore_spdf$Information, color = "red" , radius=2)
    m
    return(m)
  }
  #blue
  male_dots <- function(m){
    m <- leaflet()
    m <- addTiles(m)
    m <- addCircleMarkers(m, data=convstore_spdf, popup=convstore_spdf$Information, radius=2)
    m
    return(m)
  }
  #==============================================================================
  
   if(input$style == "default"){
     disable("density_degree")
     
    if(input$gender=="both"){
        m <- leaflet()
        m <- addTiles(m)
        m <- addCircleMarkers(m, data=convstore_spdf, popup=convstore_spdf$Information, color = "purple" , radius=2)
        m
    }
    else if(input$gender=="male"){
        male_dots(m)
    }
     else if(input$gender=="female"){
       female_dots(m)
    }
   }
  #==========- 4. clustering ===================
    else if(input$style == "kmeans"){
        disable("density_degree")
        disable("density_color")
      
        #+++++++++++++++ K-Means clustering +++++++++++++++++++++++++++++
        #R use an efficient algorithm that partitions the observations into k groups.
        ##------- K-Means clustering ---------------------
        cl <- kmeans(coordinates(convstore_spdf), centers=3) 
        # Find the best k for k-means algorithm by varying 'k' and calculate 'withinss'
        
        ##### ALL DES: Code from http://www.r-statistics.com/2013/08/k-means-clustering-from-r-in-action/
        wssplot <- function(data, nc=15, seed=12345){ #nc is the max no.of clusters to consider
          #The data parameter is the numeric dataset to be analyzed.
          wss <- (nrow(data)-1)*sum(apply(data,2,var))
          for (i in 2:nc){
            set.seed(seed)
            wss[i] <- sum(kmeans(data, centers=i, iter.max=100, nstart=100)$withinss)
            #adding nstart=100 will generate 100 initial configurations
          }
          qplot(1:nc, wss, xlab="Number of Clusters",
                ylab="Sum of squares within clusters", log="y")
        }
        wssplot(coordinates(convstore_spdf))
        
        # K-mean clustering
        cl <- kmeans(coordinates(convstore_spdf), centers=5, iter.max  = 20, nstart = 10)
        plot(convstore_spdf, pch=16, col=cl$cluster)
          #readline(prompt = "Press return to continue.")
        # Plot with RgoogleMaps
        # qmap("bangkok",zoom=10, l) + 
        #   geom_point(aes(x=coordinates(convstore_spdf)[,1], y=coordinates(convstore_spdf)[,2],
        #                  colour=factor(cl$cluster)), 
        #              data=convstore_spdf@data)
        
        ##------- * Plot location of convenience stores with Leaflet ----------
        m <- leaflet() 
        m <- addTiles(m)
        # set color palette
        palette <- colorFactor(rainbow(nrow(cl$centers)), cl$cluster)
        m <- addCircleMarkers(m, data=convstore_spdf, popup=convstore_spdf$Information,
                              radius=5, stroke=FALSE, 
                              fillColor= ~palette(cl$cluster), fillOpacity = 0.8)
        m <- addCircleMarkers(m, data=cl$centers, radius=10, fill=FALSE)
        m
        #--------------------------------------------------------------------
    }
      
    else if(input$style == "pam"){
        disable("density_degree")
        disable("density_color")
      
        #+++++++++++++++ PAM clustering +++++++++++++++++++++++++++++
        ##--------- PAM Clustering -----------------------------------------
        pamcl <- pam(coordinates(convstore_spdf), k=15)
        
        ##---------- * SAME --------------------------------------
        m <- leaflet() 
        m <- addTiles(m)
        # set color palette
        palette <- colorFactor(rainbow(nrow(pamcl$medoids)), pamcl$clustering)
        m <- addCircleMarkers(m, data=convstore_spdf, popup=convstore_spdf$Information,
                              radius=5, stroke=FALSE, 
                              fillColor= ~palette(pamcl$clustering), fillOpacity = 0.8)
        m <- addCircleMarkers(m, data=pamcl$medoids, radius=10, fill=FALSE)
        m
        #--------------------------------------------------------------------
      }
      
    else if(input$style == "hie"){
        disable("density_degree")
        disable("density_color")
      
        #+++++++++++++++ Hierarchical clustering +++++++++++++++++++++
        #requires that the number of clusters to extract be specified in advance.
        ##---------- Hierarchical clustering ----------------------
        # Compute the distanct matrix of the locations. 
        distMat <- dist(coordinates(convstore_spdf))
        # Compute hierarchical clustering.  
        hcl <- hclust(distMat, method="average")
        plot(hcl, hang=-1, cex=.8, main="Average Linkage Clustering")
        
        # Obtain the final cluster solution
        hcluster <- cutree(hcl, k=5)
        table(hcluster)
        
        ##-------- * SAME ------------
        m <- leaflet() 
        m <- addTiles(m)
        # set color palette
        palette <- colorFactor(rainbow(nlevels(as.factor(hcluster))), hcluster)
        m <- addCircleMarkers(m, data=convstore_spdf, popup=convstore_spdf$Information,
                              radius=5, stroke=FALSE, 
                              fillColor= ~palette(hcluster), fillOpacity = 0.8)
        m
        #--------------------------------------------------------------------  
    }
  #==================================================
    else if(input$style == "den"){
      enable("density_degree")
      enable("density_color")
      
      m <- leaflet()
      m <- addTiles(m)
      m <- addMouseCoordinates(m)
     
      #------ delay warning -------
      if(input$density_degree == 100 || input$density_degree == 200 || input$density_degree == 300){
        showModal(modalDialog(
          title = paste("The smoothed intensity: ", input$density_degree, "It will take several minutes to create the visualization in a density map")
        ))
      }
      dlat <- (DegreeLatitudeOf1KM / 1000 ) * input$density_degree
      dlong <- (DegreeLongitudeOf1KM / 1000) * input$density_degree
      
      #Add set the initial view when map appears.
      m <- setView(m, lng=100.5346, lat=13.7455, zoom=13) # Center of Bangkok at BTS Siam Square
      
      #3.4 Plot a density map (IT TAKES 3 MINS)
      #----- 3.4.1. SETTING ----------------------------
      #Change the number of pixels in an image
      w <- (100.952-99.829)/dlat  
      h <- (14.276-13.425)/dlong
      spatstat.options(npixel=c(ceiling(w),ceiling(h))) #Change the default (In spatstat package, the default number of pixels in an image is 128*128=16384 pixels.)
      
      #Create a spatial point pattern
      a_spdf <- convstore_spdf
      a_ppp <- as.ppp(a_spdf) #Convert spatial data frame to spatial point pattern for use by spatstat package.
      
      #Compute a kernel smoothed intensity from a point pattern 
      #(sigma can be ether a numerical value or a function such as bw.ppl, bw.diggle, bw.scott.)
      a_density <- density.ppp(a_ppp, sigma=bw.ppl(a_ppp))
      a_density_raster <- raster(a_density, crs="+proj=longlat +init=epsg:4326") #me = 3857
      
      #Set the color (see "COLORS for a density map")
      if(input$density_color == "red"){
        pal <- colorNumeric(black_to_red_9, values(a_density_raster), na.color="transparent") # black to red 9
      }
      else if(input$density_color == "blue"){
        pal <- colorNumeric(black_to_blue_9, values(a_density_raster), na.color="transparent") # black to blue 9
      }
      else if(input$density_color == "gold"){
        pal <- colorNumeric(black_to_gold_6, values(a_density_raster), na.color="transparent") # black to gold 6
      }
      
      #----- 3.4.2. PLOT ----------------------------
      m <- addRasterImage(m, a_density_raster, colors=pal, opacity=0.5)
      m <- addCircleMarkers(m, data=a_spdf, popup=a_spdf$Information, radius=2, stroke=FALSE, fillColor="white", fillOpacity=0.5)
      #-----Addition-----------------------
      #Add a box describing the meaning of each color in pal.
      #m <- addLegend(m, pal=pal, values=a_spdf$from_start_days, title="Timestamp (day)")
      #------------------------------------
      m
      
      # enable("divide") here cause a BUG
      }
  }

)
#&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&


#&&&&&&&&&&&&&&&& Output &&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&
#https://shiny.rstudio.com/reference/shiny/latest/renderPlot.html
output$bkkmapplot <- renderLeaflet({
  
  #----- For plotting Bar Graph ------
  output$bargraph <- renderPlot({
    par(bg = 'grey')
    #barplot(total_pop_bar[1:2], las=2, xlab="Bangkok Population already divided", ylab="The amount", col=c("blue","red"), names =c("Male", "Female"),legend = rownames(total_pop_bar[1:2]), cex.names=1.0)
    bardata <- data.frame( Male= total_pop_bar[1], Female= total_pop_bar[2] )
    barplot(as.matrix(bardata), main="Bangkok Population (Gender)", ylab="Dots on the map", beside=TRUE, col= c("blue","red"))
    legend(13, 12, c("Male","Female"), cex=0.6, fill= c("purple","yellow"))
  })
  
  #ONLY m() is OK- IT TAKES SEVERAL DAYS TO DEBUG !!!
  m()
})

#&&&&&&&&&&&&&&&& Download &&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&
#http://stackoverflow.com/questions/28733019/shiny-r-using-the-downloadbutton-function
#https://shiny.rstudio.com/articles/download.html
#STILL IMPERFECT
output$downloadData <- downloadHandler(
  filename = function() {
    paste('my-report', sep = '.','png')
  },
  content = function(file) {
    src <- normalizePath('output/dots_output.Rmd')
    # temporarily switch to the temp dir, in case you do not have write permission to the current working directory
    owd <- setwd(tempdir())
    on.exit(setwd(owd))
    file.copy(src, 'dots_output.Rmd', overwrite = TRUE)
    out <- render('dots_output.Rmd')
    file.rename(out, file)
    
    #------ delay warning -------
    showModal(modalDialog(
      title = paste("View your 'Dots' output in a web browser")
    ))
  }
)

output$downloadData2 <- downloadHandler(
  filename = function() {
    paste('my-report', sep = '.','png')
  },
  content = function(file) {
    src <- normalizePath('output/bargraph_output.Rmd')
    # temporarily switch to the temp dir, in case you do not have write permission to the current working directory
    owd <- setwd(tempdir())
    on.exit(setwd(owd))
    file.copy(src, 'bargraph_output.Rmd', overwrite = TRUE)
    out <- render('bargraph_output.Rmd')
    file.rename(out, file)
    
    #------ delay warning -------
    showModal(modalDialog(
      title = paste("View your 'Bar Graph' output in a web browser")
    ))
  }
)

  #only plotting saved #m is invalid
#mapshot(m(), file = "C:/Users/Vip/Desktop/mmm.png")
#webshot(" url " , file = "C:/Users/Vip/Desktop/mmm.png", cliprect = "viewport")
#observeEvent(input$download, {})

})


#&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&

#Run by coding
# setwd('C:/../<folder the dir of <<folder: keeps server,ui>>')
# runApp('<<folder>>')
#--or--
# runApp('C:/../<<folder>>')