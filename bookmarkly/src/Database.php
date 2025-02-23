<?php

class Database {
    public $data;
    public $file;

    public function __construct($file = null) {
        if ($file === null) {
            // Gebruik een directory waar we schrijfrechten hebben
            $this->file = dirname(__DIR__) . '/data/database.json';
        } else {
            $this->file = $file;
        }
        $this->load();
    }

    private function load() {
        if (file_exists($this->file)) {
            $content = file_get_contents($this->file);
            if ($content) {
                $this->data = json_decode($content, true);
                if ($this->data === null) {
                    $this->data = ['categories' => [], 'bookmarks' => []];
                }
            } else {
                $this->data = ['categories' => [], 'bookmarks' => []];
            }
        } else {
            $this->data = ['categories' => [], 'bookmarks' => []];
        }
        
        // Alleen proberen te saven als het bestand nog niet bestaat
        if (!file_exists($this->file)) {
            $this->save();
        }
    }

    public function save() {
        $directory = dirname($this->file);
        if (!file_exists($directory)) {
            // Probeer directory aan te maken, maar geen error als het niet lukt
            @mkdir($directory, 0775, true);
        }

        // Gebruik error suppression om waarschuwingen te voorkomen
        $success = @file_put_contents($this->file, json_encode($this->data, JSON_PRETTY_PRINT));
        if ($success === false) {
            error_log("Kon data niet opslaan in " . $this->file);
            throw new Exception("Kon de data niet opslaan. Controleer de bestandsrechten.");
        }
        return true;
    }

    public function getCategories() {
        if (!isset($this->data['categories'])) {
            $this->data['categories'] = [];
        }

        // Voeg Uncategorised toe als het nog niet bestaat
        $hasUncategorised = false;
        foreach ($this->data['categories'] as $category) {
            if ($category['id'] === 'uncategorised') {
                $hasUncategorised = true;
                break;
            }
        }

        if (!$hasUncategorised) {
            $this->data['categories'][] = [
                'id' => 'uncategorised',
                'name' => 'Uncategorised',
                'order' => 999,  // Altijd laatste
                'hidden' => true // Verborgen op voorpagina
            ];
        }

        // Sorteer categorieën op volgorde voordat ze worden teruggegeven
        $categories = array_values(array_filter($this->data['categories'], function($category) {
            return $category !== null;
        }));

        usort($categories, function($a, $b) {
            $orderA = $a['order'] ?? 99;
            $orderB = $b['order'] ?? 99;
            return $orderA - $orderB;
        });

        return $categories;
    }

    public function addCategory($name) {
        if (!isset($this->data['categories'])) {
            $this->data['categories'] = [];
        }
        
        $newCategory = [
            'id' => uniqid(),
            'name' => $name
        ];
        
        $this->data['categories'][] = $newCategory;
        $this->save();
        return $newCategory;
    }

    public function getBookmarks() {
        return isset($this->data['bookmarks']) ? $this->data['bookmarks'] : [];
    }

    public function getBookmarksByCategory($categoryId) {
        // Haal alle bookmarks van deze categorie
        $bookmarks = array_values(array_filter($this->data['bookmarks'], function($b) use ($categoryId) {
            return $b['categoryId'] === $categoryId;
        }));
        
        // Sorteer de bookmarks op basis van hun positie in de hoofdarray
        usort($bookmarks, function($a, $b) {
            $aIndex = array_search($a['id'], array_column($this->data['bookmarks'], 'id'));
            $bIndex = array_search($b['id'], array_column($this->data['bookmarks'], 'id'));
            return $aIndex - $bIndex;
        });
        
        return $bookmarks;
    }

    public function addBookmark($bookmark) {
        if (!isset($this->data['bookmarks'])) {
            $this->data['bookmarks'] = [];
        }
        
        // Als geen categorie is geselecteerd, gebruik Uncategorised
        if (empty($bookmark['categoryId'])) {
            $bookmark['categoryId'] = 'uncategorised';
        }
        
        // Zorg ervoor dat favorite een boolean is
        $bookmark['favorite'] = isset($bookmark['favorite']) && $bookmark['favorite'] === true;
        
        // Als het een favoriet is, geef het een positie
        if ($bookmark['favorite']) {
            $favorites = $this->getFavorites();
            $bookmark['favorite_position'] = count($favorites);
        }
        
        $newBookmark = array_merge(['id' => uniqid()], $bookmark);
        $this->data['bookmarks'][] = $newBookmark;
        $this->save();
        return $newBookmark;
    }

    public function getFavorites() {
        // Filter favorieten
        $favorites = array_values(array_filter($this->data['bookmarks'], function($bookmark) {
            return isset($bookmark['favorite']) && $bookmark['favorite'] === true;
        }));
        
        // Zorg ervoor dat alle favorieten een positie hebben
        foreach ($favorites as $index => &$favorite) {
            if (!isset($favorite['favorite_position'])) {
                $favorite['favorite_position'] = $index;
                // Update ook in de hoofdarray
                foreach ($this->data['bookmarks'] as &$bookmark) {
                    if ($bookmark['id'] === $favorite['id']) {
                        $bookmark['favorite_position'] = $index;
                        break;
                    }
                }
            }
        }
        
        // Sorteer op favorite_position
        usort($favorites, function($a, $b) {
            return ($a['favorite_position'] ?? 0) - ($b['favorite_position'] ?? 0);
        });
        
        $this->save(); // Sla eventuele nieuwe posities op
        return $favorites;
    }

    public function moveCategoryUp($categoryId) {
        $categories = $this->getCategories(); // Dit geeft al gesorteerde categorieën
        $currentPosition = -1;
        
        // Vind de huidige positie
        foreach ($categories as $index => $category) {
            if ($category['id'] === $categoryId) {
                $currentPosition = $index;
                break;
            }
        }
        
        if ($currentPosition > 0) {
            // Bereken nieuwe order waarde
            $prevOrder = $categories[$currentPosition - 1]['order'] ?? 99;
            $currentOrder = $categories[$currentPosition]['order'] ?? 99;
            
            // Als er ruimte is tussen de orders, plaats het er tussenin
            if ($prevOrder + 1 < $currentOrder) {
                $newOrder = $prevOrder + 1;
            } else {
                // Anders, verschuif alles
                $newOrder = $prevOrder;
                $categories[$currentPosition - 1]['order'] = $currentOrder;
            }
            
            // Update de order van de huidige categorie
            foreach ($this->data['categories'] as &$category) {
                if ($category['id'] === $categoryId) {
                    $category['order'] = $newOrder;
                    break;
                }
            }
            
            // Als we orders hebben verschoven, update de andere categorie
            if ($newOrder === $prevOrder) {
                foreach ($this->data['categories'] as &$category) {
                    if ($category['id'] === $categories[$currentPosition - 1]['id']) {
                        $category['order'] = $currentOrder;
                        break;
                    }
                }
            }
            
            $this->save();
            return true;
        }
        return false;
    }

    public function moveCategoryDown($categoryId) {
        $categories = $this->getCategories(); // Dit geeft al gesorteerde categorieën
        $currentPosition = -1;
        
        // Vind de huidige positie
        foreach ($categories as $index => $category) {
            if ($category['id'] === $categoryId) {
                $currentPosition = $index;
                break;
            }
        }
        
        if ($currentPosition !== -1 && $currentPosition < count($categories) - 1) {
            // Bereken nieuwe order waarde
            $nextOrder = $categories[$currentPosition + 1]['order'] ?? 99;
            $currentOrder = $categories[$currentPosition]['order'] ?? 99;
            
            // Als er ruimte is tussen de orders, plaats het er tussenin
            if ($currentOrder + 1 < $nextOrder) {
                $newOrder = $currentOrder + 1;
            } else {
                // Anders, verschuif alles
                $newOrder = $nextOrder;
                $categories[$currentPosition + 1]['order'] = $currentOrder;
            }
            
            // Update de order van de huidige categorie
            foreach ($this->data['categories'] as &$category) {
                if ($category['id'] === $categoryId) {
                    $category['order'] = $newOrder;
                    break;
                }
            }
            
            // Als we orders hebben verschoven, update de andere categorie
            if ($newOrder === $nextOrder) {
                foreach ($this->data['categories'] as &$category) {
                    if ($category['id'] === $categories[$currentPosition + 1]['id']) {
                        $category['order'] = $currentOrder;
                        break;
                    }
                }
            }
            
            $this->save();
            return true;
        }
        return false;
    }

    public function moveBookmarkUp($bookmarkId, $categoryId) {
        // Maak een array met alleen de bookmarks van deze categorie
        $categoryBookmarks = [];
        foreach ($this->data['bookmarks'] as $key => $bookmark) {
            if ($bookmark['categoryId'] === $categoryId) {
                $categoryBookmarks[] = $bookmark;
            }
        }

        // Vind de huidige bookmark in de gefilterde lijst
        $currentIndex = -1;
        foreach ($categoryBookmarks as $index => $bookmark) {
            if ($bookmark['id'] === $bookmarkId) {
                $currentIndex = $index;
                break;
            }
        }

        // Als we de bookmark hebben gevonden en het is niet de eerste
        if ($currentIndex > 0) {
            // Wissel met de vorige bookmark
            $temp = $categoryBookmarks[$currentIndex];
            $categoryBookmarks[$currentIndex] = $categoryBookmarks[$currentIndex - 1];
            $categoryBookmarks[$currentIndex - 1] = $temp;

            // Update de volledige bookmarks array
            $newBookmarks = [];
            $categoryIndex = 0;

            foreach ($this->data['bookmarks'] as $bookmark) {
                if ($bookmark['categoryId'] === $categoryId) {
                    $newBookmarks[] = $categoryBookmarks[$categoryIndex];
                    $categoryIndex++;
                } else {
                    $newBookmarks[] = $bookmark;
                }
            }

            $this->data['bookmarks'] = $newBookmarks;
            $this->save();
            return true;
        }

        return false;
    }

    public function moveBookmarkDown($bookmarkId, $categoryId) {
        // Maak een array met alleen de bookmarks van deze categorie
        $categoryBookmarks = [];
        foreach ($this->data['bookmarks'] as $key => $bookmark) {
            if ($bookmark['categoryId'] === $categoryId) {
                $categoryBookmarks[] = $bookmark;
            }
        }

        // Vind de huidige bookmark in de gefilterde lijst
        $currentIndex = -1;
        foreach ($categoryBookmarks as $index => $bookmark) {
            if ($bookmark['id'] === $bookmarkId) {
                $currentIndex = $index;
                break;
            }
        }

        // Als we de bookmark hebben gevonden en het is niet de laatste
        if ($currentIndex !== -1 && $currentIndex < count($categoryBookmarks) - 1) {
            // Wissel met de volgende bookmark
            $temp = $categoryBookmarks[$currentIndex];
            $categoryBookmarks[$currentIndex] = $categoryBookmarks[$currentIndex + 1];
            $categoryBookmarks[$currentIndex + 1] = $temp;

            // Update de volledige bookmarks array
            $newBookmarks = [];
            $categoryIndex = 0;

            foreach ($this->data['bookmarks'] as $bookmark) {
                if ($bookmark['categoryId'] === $categoryId) {
                    $newBookmarks[] = $categoryBookmarks[$categoryIndex];
                    $categoryIndex++;
                } else {
                    $newBookmarks[] = $bookmark;
                }
            }

            $this->data['bookmarks'] = $newBookmarks;
            $this->save();
            return true;
        }

        return false;
    }

    private function findIndex($array, $id) {
        foreach ($array as $index => $item) {
            if ($item['id'] === $id) {
                return $index;
            }
        }
        return false;
    }

    public function deleteCategory($categoryId) {
        // Verwijder eerst alle bookmarks in deze categorie
        $this->data['bookmarks'] = array_filter($this->data['bookmarks'], function($bookmark) use ($categoryId) {
            return $bookmark['categoryId'] !== $categoryId;
        });
        
        // Verwijder dan de categorie
        $this->data['categories'] = array_filter($this->data['categories'], function($category) use ($categoryId) {
            return $category['id'] !== $categoryId;
        });
        
        $this->save();
        return true;
    }

    public function deleteBookmark($id, $fromFavorites = false) {
        // Als we verwijderen vanuit favorieten, verwijder alleen de favoriet status
        if ($fromFavorites) {
            foreach ($this->data['bookmarks'] as &$bookmark) {
                if ($bookmark['id'] === $id) {
                    $bookmark['favorite'] = false;
                    unset($bookmark['favorite_position']);
                    break;
                }
            }
        } else {
            // Anders verwijder de hele bookmark
            $this->data['bookmarks'] = array_filter($this->data['bookmarks'], function($bookmark) use ($id) {
                return $bookmark['id'] !== $id;
            });
        }
        
        $this->save();
        return true;
    }

    public function getSettings() {
        if (!isset($this->data['settings'])) {
            $this->data['settings'] = [
                'theme' => 'transparent',
                'language' => 'en',
                'background_image' => 'bg/mist.jpg',
                'background_brightness' => '100',
                'background_saturation' => '100',
                'debug_mode' => false,
                'target_blank' => true,
                'dashboard_title' => 'BOOKMARKS',
                'protect_dashboard' => false,  // Nieuwe instelling
                'remember_duration' => '2w'    // Nieuwe instelling (2w, 4w, 3m, 6m)
            ];
        }
        return $this->data['settings'];
    }

    public function updateSettings($settings) {
        $this->data['settings'] = array_merge($this->getSettings(), $settings);
        $this->save();
    }

    public function getCustomCss() {
        return $this->data['custom_css'] ?? '';
    }

    public function updateCustomCss($css) {
        $this->data['custom_css'] = $css;
        $this->save();
    }

    public function getBookmark($id) {
        foreach ($this->data['bookmarks'] as $bookmark) {
            if ($bookmark['id'] === $id) {
                return $bookmark;
            }
        }
        return null;
    }

    public function updateBookmark($updatedBookmark) {
        $bookmarks = &$this->data['bookmarks'];
        
        // Zorg ervoor dat favorite een boolean is
        if (isset($updatedBookmark['favorite'])) {
            $updatedBookmark['favorite'] = (bool)$updatedBookmark['favorite'];
            
            // Als het een nieuwe favoriet is, geef het een positie
            if ($updatedBookmark['favorite']) {
                $favorites = $this->getFavorites();
                $updatedBookmark['favorite_position'] = count($favorites);
            }
        }
        
        foreach ($bookmarks as $key => $bookmark) {
            if ($bookmark['id'] === $updatedBookmark['id']) {
                $bookmarks[$key] = $updatedBookmark;
                $this->save();
                return true;
            }
        }
        return false;
    }

    public function moveFavoriteUp($id) {
        $favorites = $this->getFavorites(); // Dit zorgt ervoor dat alle posities zijn geïnitialiseerd
        $currentIndex = -1;
        
        foreach ($favorites as $index => $favorite) {
            if ($favorite['id'] === $id) {
                $currentIndex = $index;
                break;
            }
        }
        
        if ($currentIndex > 0) {
            // Wissel de posities
            $temp = $favorites[$currentIndex];
            $favorites[$currentIndex] = $favorites[$currentIndex - 1];
            $favorites[$currentIndex - 1] = $temp;
            
            // Update de posities
            foreach ($favorites as $index => $favorite) {
                foreach ($this->data['bookmarks'] as &$bookmark) {
                    if ($bookmark['id'] === $favorite['id']) {
                        $bookmark['favorite_position'] = $index;
                        break;
                    }
                }
            }
            
            $this->save();
            return true;
        }
        return false;
    }

    public function moveFavoriteDown($id) {
        $favorites = $this->getFavorites(); // Dit zorgt ervoor dat alle posities zijn geïnitialiseerd
        $currentIndex = -1;
        
        foreach ($favorites as $index => $favorite) {
            if ($favorite['id'] === $id) {
                $currentIndex = $index;
                break;
            }
        }
        
        if ($currentIndex !== -1 && $currentIndex < count($favorites) - 1) {
            // Wissel de posities
            $temp = $favorites[$currentIndex];
            $favorites[$currentIndex] = $favorites[$currentIndex + 1];
            $favorites[$currentIndex + 1] = $temp;
            
            // Update de posities
            foreach ($favorites as $index => $favorite) {
                foreach ($this->data['bookmarks'] as &$bookmark) {
                    if ($bookmark['id'] === $favorite['id']) {
                        $bookmark['favorite_position'] = $index;
                        break;
                    }
                }
            }
            
            $this->save();
            return true;
        }
        return false;
    }

    public function updateCategory($updatedCategory) {
        foreach ($this->data['categories'] as &$category) {
            if ($category['id'] === $updatedCategory['id']) {
                $category['name'] = $updatedCategory['name'];
                $this->save();
                return true;
            }
        }
        return false;
    }

    public function updateCategoriesOrder($categories) {
        // Zorg ervoor dat we een gewone array opslaan, geen object
        $this->data['categories'] = array_values($categories);
        $this->save();
    }

    public function cleanupDatabase() {
        // Converteer categorieën van object naar array en verwijder null waarden
        if (isset($this->data['categories'])) {
            $cleanCategories = [];
            foreach ($this->data['categories'] as $category) {
                if ($category !== null) {
                    $cleanCategories[] = $category;
                }
            }
            $this->data['categories'] = array_values($cleanCategories);
        }

        // Verwijder bookmarks die naar niet-bestaande categorieën verwijzen
        $validCategoryIds = array_column($this->data['categories'], 'id');
        $validCategoryIds[] = 'favorites'; // Voeg favorites toe als geldige categorie
        
        if (isset($this->data['bookmarks'])) {
            $this->data['bookmarks'] = array_values(array_filter($this->data['bookmarks'], function($bookmark) use ($validCategoryIds) {
                return $bookmark['categoryId'] === null || in_array($bookmark['categoryId'], $validCategoryIds);
            }));
        }

        $this->save();
    }

    public function updateCategoryOrder($categoryId, $order) {
        foreach ($this->data['categories'] as &$category) {
            if ($category['id'] === $categoryId) {
                $category['order'] = (int)$order;
                break;
            }
        }

        // Sorteer categorieën op volgorde
        usort($this->data['categories'], function($a, $b) {
            $orderA = $a['order'] ?? 99;
            $orderB = $b['order'] ?? 99;
            return $orderA - $orderB;
        });

        return $this->save();
    }
} 