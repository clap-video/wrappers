import ClapAPI

clap = ClapAPI("client_id", "client_secret")
project = clap.create_project(user=54,
                              webhook_infos={"program": 34},
                              realty={
                                  "fr_title": "Super appartement",
                                  "city": "Grenoble"
                              },
                              photos=["https://monagence.com/photo1.jpg", "https://monagence.com/photo2.jpg", "https://monagence.com/photo3.jpg"])
