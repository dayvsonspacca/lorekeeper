package
{
   import flash.display.DisplayObject;
   import flash.display.DisplayObjectContainer;
   import flash.display.Loader;
   import flash.display.MovieClip;
   import flash.display.StageAlign;
   import flash.display.StageScaleMode;
   import flash.events.Event;
   import flash.events.IOErrorEvent;
   import flash.events.SecurityErrorEvent;
   import flash.external.ExternalInterface;
   import flash.geom.ColorTransform;
   import flash.geom.Rectangle;
   import flash.net.URLRequest;
   import flash.system.ApplicationDomain;
   import flash.system.LoaderContext;

   public class AvatarPreview extends MovieClip
   {
      [Embed(source="../../../var/cache/client/Game.swf", symbol="mcSkel")]
      private static const Skeleton:Class;

      private static const FOOT_SWITCHES:String = "8I,17F,28F,41F,54F,69I,86I,100I,118I,132I,167I,187I,211I,246I,263I,272F,281I,290F,314I,347F,365I,368F,394I,459I,476F,503I,560F,591I,600I,622I,703I,723I,753I,782I,810I,828F,850I,857I,887I,911I,932I,959I,985I,1003I,1015I,1050I,1072I,1084I,1098I,1113I,1123I,1137I,1152I,1164I,1180I,1193I,1205I,1223I,1248I,1255I,1268I,1285F,1296I,1298F,1321I,1341I,1356F,1372I,1406I,1444I,1453I,1526I,1564I,1580I,1590I,1622I,1649I,1675I,1692I,1706I,1726I,1768I,1783I,1796I,1820I,1838I,1858I,1868I";

      private static const FIT_RATIO:Number = 0.78;

      private static const FRONT_PARTS:Array = ["chest", "hip", "frontthigh", "frontshin", "idlefoot", "frontfoot", "frontshoulder", "fronthand", "robe"];

      private static const HIDDEN_PARTS:Array = ["cape", "backhair", "robe", "backrobe", "pvpFlag", "weapon", "weaponOff", "weaponFist", "weaponFistOff", "shield", "hitbox", "emoteFX", "arrow"];

      private var domain:ApplicationDomain = new ApplicationDomain(ApplicationDomain.currentDomain);
      private var skeleton:MovieClip;
      private var params:Object;
      private var front:ColorTransform;
      private var back:ColorTransform;
      private var bounds:Rectangle;
      private var palette:Object;
      private var loopStart:int = 0;
      private var loopEnd:int = 0;

      public function AvatarPreview()
      {
         super();
         stage.align = StageAlign.TOP_LEFT;
         stage.scaleMode = StageScaleMode.NO_SCALE;

         params = stage.loaderInfo.parameters;
         front = silhouette(uint(params.sColor || "0xA1A1AA"));
         back = new ColorTransform(0, 0, 0);
         palette = {
            "Skin": uint(params.sColor || "0xA1A1AA"),
            "Hair": uint(params.sColor || "0xA1A1AA"),
            "Eye": uint(params.sBackColor || "0x71717A"),
            "Base": 0x4B5563,
            "Trim": 0xB08D57,
            "Accessory": 0x7F1D1D
         };

         skeleton = new Skeleton();
         addChild(skeleton);
         skeleton.gotoAndStop("Idle");
         prepareSkeleton();

         if (ExternalInterface.available)
         {
            ExternalInterface.addCallback("playAnim", playAnim);
         }

         stage.addEventListener(Event.RESIZE, onResize, false, 0, true);
         addEventListener(Event.ENTER_FRAME, onFrame, false, 0, true);

         if (!params.sFile)
         {
            fitAndCenter();
            notify("onAvatarLoaded", "");
            return;
         }

         var loader:Loader = new Loader();
         loader.contentLoaderInfo.addEventListener(Event.COMPLETE, onItemLoaded, false, 0, true);
         loader.contentLoaderInfo.addEventListener(IOErrorEvent.IO_ERROR, onError, false, 0, true);
         loader.contentLoaderInfo.addEventListener(SecurityErrorEvent.SECURITY_ERROR, onError, false, 0, true);
         loader.load(new URLRequest(String(params.sBase || "") + params.sFile), new LoaderContext(false, domain));
      }

      public function mcSetColor(clip:MovieClip, location:String, shade:String) : void
      {
         var transform:ColorTransform = new ColorTransform();
         transform.color = uint(palette[location] || palette.Base);
         var step:int = shade.toUpperCase() == "LIGHT" ? 100 : shade.toUpperCase() == "DARK" ? -50 : shade.toUpperCase() == "DARKER" ? -125 : 0;
         transform.redOffset += location == "Skin" && step == -50 ? -25 : step;
         transform.greenOffset += step;
         transform.blueOffset += step;
         clip.transform.colorTransform = transform;
      }

      public function playAnim(label:String) : void
      {
         var labels:Array = skeleton.currentLabels;
         for (var i:int = 0; i < labels.length; i++)
         {
            if (labels[i].name != label)
            {
               continue;
            }

            loopStart = labels[i].frame;
            loopEnd = i + 1 < labels.length ? labels[i + 1].frame - 1 : skeleton.totalFrames;
            if (label == "Idle")
            {
               loopEnd = loopStart;
            }

            show(loopStart);
            fitAndCenter();
            return;
         }
      }

      private function prepareSkeleton() : void
      {
         for each (var hidden:String in HIDDEN_PARTS)
         {
            if (part(hidden) != null)
            {
               part(hidden).visible = false;
            }
         }

         part("frontfoot").visible = false;
         part("idlefoot").visible = true;

         for each (var name:String in FRONT_PARTS)
         {
            paint(part(name), front);
         }

         paint(part("backhand"), back);

         var head:DisplayObjectContainer = part("head") as DisplayObjectContainer;
         paintChild(head, "face", front);
         paintChild(head, "hair", front);
         if (head.getChildByName("helm") != null)
         {
            clear(head.getChildByName("helm") as DisplayObjectContainer);
         }
      }

      private function onItemLoaded(e:Event) : void
      {
         var slot:String = String(params.sSlot || "");
         var link:String = String(params.sLink || "");

         if (slot == "armor")
         {
            wearArmor(link, String(params.sGender || "M"));
         }
         else
         {
            var create:Function = factory(definition(link), e.target.content as DisplayObject);
            if (create == null)
            {
               notify("onAvatarFailed", "");
               return;
            }

            if (slot == "weapon")
            {
               wearWeapon(create, String(params.sType || ""));
            }
            else if (slot == "helm")
            {
               wearHelm(create, definition(link + "_backhair"));
            }
            else if (slot == "cape")
            {
               wearCape(create);
            }
         }

         fitAndCenter();
         notify("onAvatarLoaded", "");
      }

      private function wearWeapon(create:Function, type:String) : void
      {
         if (type == "Gauntlet")
         {
            holdInHand(part("fronthand") as DisplayObjectContainer, create);
            holdInHand(part("backhand") as DisplayObjectContainer, create);
            return;
         }

         place(part("weapon") as DisplayObjectContainer, create());
         if (type == "Dagger")
         {
            place(part("weaponOff") as DisplayObjectContainer, create());
         }
      }

      private function holdInHand(hand:DisplayObjectContainer, create:Function) : void
      {
         var weapon:DisplayObject = create();
         if (weapon == null)
         {
            return;
         }

         weapon.scaleX = -0.8;
         weapon.scaleY = 0.8;
         hand.addChildAt(weapon, Math.min(1, hand.numChildren));
      }

      private function wearHelm(create:Function, backHair:Class) : void
      {
         var head:DisplayObjectContainer = part("head") as DisplayObjectContainer;
         var helm:DisplayObjectContainer = head.getChildByName("helm") as DisplayObjectContainer;
         place(helm, create());
         head.getChildByName("hair").visible = false;

         if (backHair != null)
         {
            place(part("backhair") as DisplayObjectContainer, new backHair());
         }
      }

      private function wearCape(create:Function) : void
      {
         var cape:MovieClip = create() as MovieClip;
         place(part("cape") as DisplayObjectContainer, cape);
         if (cape != null && hasLabel(cape, "Idle"))
         {
            cape.gotoAndStop("Idle");
         }
      }

      private function wearArmor(link:String, gender:String) : void
      {
         var head:DisplayObjectContainer = part("head") as DisplayObjectContainer;
         var face:Class = definition(link + gender + "Head");
         if (face != null)
         {
            var old:DisplayObject = head.getChildByName("face");
            if (old != null)
            {
               head.removeChild(old);
            }

            var created:DisplayObject = head.addChildAt(new face(), 0);
            created.name = "face";
         }

         swap(link + gender + "Chest", ["chest"]);
         swap(link + gender + "Hip", ["hip"]);
         swap(link + gender + "FootIdle", ["idlefoot"]);
         swap(link + gender + "Foot", ["frontfoot", "backfoot"]);
         swap(link + gender + "Shoulder", ["frontshoulder", "backshoulder"]);
         swap(link + gender + "Thigh", ["frontthigh", "backthigh"]);
         swap(link + gender + "Shin", ["frontshin", "backshin"]);
         swap(link + gender + "Robe", ["robe"]);
         swap(link + gender + "RobeBack", ["backrobe"]);

         var hand:Class = definition(link + gender + "Hand");
         if (hand != null)
         {
            replaceFirst(part("fronthand") as DisplayObjectContainer, new hand());
            replaceFirst(part("backhand") as DisplayObjectContainer, new hand()).transform.colorTransform = new ColorTransform(0, 0, 0);
         }
      }

      private function swap(linkage:String, names:Array) : void
      {
         var asset:Class = definition(linkage);
         if (asset == null)
         {
            return;
         }

         for each (var name:String in names)
         {
            var container:DisplayObjectContainer = part(name) as DisplayObjectContainer;
            if (container == null)
            {
               continue;
            }

            replaceFirst(container, new asset());
            if (name == "robe" || name == "backrobe")
            {
               container.visible = true;
            }
         }
      }

      private function replaceFirst(container:DisplayObjectContainer, child:DisplayObject) : DisplayObject
      {
         if (container.numChildren > 0)
         {
            container.removeChildAt(0);
         }

         return container.addChildAt(child, 0);
      }

      private function place(container:DisplayObjectContainer, child:DisplayObject) : void
      {
         if (child == null)
         {
            return;
         }

         clear(container);
         container.addChild(child);
         container.visible = true;
      }

      private function clear(container:DisplayObjectContainer) : void
      {
         while (container.numChildren > 0)
         {
            container.removeChildAt(0);
         }
      }

      private static function factory(asset:Class, raw:DisplayObject) : Function
      {
         if (asset != null)
         {
            return function() : DisplayObject
            {
               return new asset();
            };
         }

         if (raw == null)
         {
            return null;
         }

         return function() : DisplayObject
         {
            var once:DisplayObject = raw;
            raw = null;
            return once;
         };
      }

      private function definition(linkage:String) : Class
      {
         try
         {
            return domain.hasDefinition(linkage) ? domain.getDefinition(linkage) as Class : null;
         }
         catch (err:Error)
         {
         }

         return null;
      }

      private function part(name:String) : DisplayObject
      {
         return skeleton.getChildByName(name);
      }

      private function paint(target:DisplayObject, color:ColorTransform) : void
      {
         var container:DisplayObjectContainer = target as DisplayObjectContainer;
         if (container == null)
         {
            return;
         }

         for (var i:int = 0; i < container.numChildren; i++)
         {
            container.getChildAt(i).transform.colorTransform = color;
         }
      }

      private function paintChild(container:DisplayObjectContainer, name:String, color:ColorTransform) : void
      {
         var child:DisplayObject = container.getChildByName(name);
         if (child != null)
         {
            child.transform.colorTransform = color;
         }
      }

      private static function silhouette(rgb:uint) : ColorTransform
      {
         return new ColorTransform(0, 0, 0, 1, (rgb >> 16) & 0xFF, (rgb >> 8) & 0xFF, rgb & 0xFF, 0);
      }

      private static function hasLabel(clip:MovieClip, label:String) : Boolean
      {
         for each (var frame:* in clip.currentLabels)
         {
            if (frame.name == label)
            {
               return true;
            }
         }

         return false;
      }

      private function onFrame(e:Event) : void
      {
         if (loopEnd <= loopStart)
         {
            return;
         }

         show(skeleton.currentFrame >= loopEnd ? loopStart : skeleton.currentFrame + 1);
      }

      private function fitAndCenter() : void
      {
         var sw:Number = stage.stageWidth;
         var sh:Number = stage.stageHeight;

         skeleton.scaleX = skeleton.scaleY = 1;
         skeleton.x = skeleton.y = 0;
         var frame:int = skeleton.currentFrame;
         bounds = visibleBounds();
         for (var f:int = loopStart + 1; f <= loopEnd; f++)
         {
            show(f);
            bounds = bounds.union(visibleBounds());
         }
         show(frame);

         var scale:Number = Math.min(sw * FIT_RATIO / bounds.width, sh * FIT_RATIO / bounds.height);
         skeleton.scaleX = skeleton.scaleY = scale;

         skeleton.x = (sw / 2) - (bounds.x + bounds.width / 2) * scale;
         skeleton.y = (sh / 2) - (bounds.y + bounds.height / 2) * scale;
      }

      private function show(frame:int) : void
      {
         skeleton.gotoAndStop(frame);

         var front:Boolean = false;
         for each (var change:String in FOOT_SWITCHES.split(","))
         {
            if (int(change.substr(0, change.length - 1)) > frame)
            {
               break;
            }

            front = change.charAt(change.length - 1) == "F";
         }

         part("idlefoot").visible = !front;
         part("frontfoot").visible = front;
      }

      private function visibleBounds() : Rectangle
      {
         var area:Rectangle = new Rectangle();
         for (var i:int = 0; i < skeleton.numChildren; i++)
         {
            var child:DisplayObject = skeleton.getChildAt(i);
            if (child.visible && child.name.indexOf("instance") != 0)
            {
               area = area.isEmpty() ? child.getBounds(skeleton) : area.union(child.getBounds(skeleton));
            }
         }

         return area;
      }

      private function onResize(e:Event) : void
      {
         fitAndCenter();
      }

      private function onError(e:Event) : void
      {
         fitAndCenter();
         notify("onAvatarFailed", "");
      }

      private function notify(callback:String, payload:String) : void
      {
         if (ExternalInterface.available)
         {
            ExternalInterface.call(callback, payload);
         }
      }
   }
}
