package
{
   import flash.display.DisplayObject;
   import flash.display.Loader;
   import flash.display.MovieClip;
   import flash.display.StageAlign;
   import flash.display.StageScaleMode;
   import flash.events.Event;
   import flash.events.IOErrorEvent;
   import flash.events.SecurityErrorEvent;
   import flash.external.ExternalInterface;
   import flash.geom.Rectangle;
   import flash.net.URLRequest;
   import flash.system.ApplicationDomain;
   import flash.system.LoaderContext;

   public class MonsterPreview extends MovieClip
   {
      private static const FIT_RATIO:Number = 0.8;

      private var domain:ApplicationDomain = new ApplicationDomain(ApplicationDomain.currentDomain);
      private var holder:MovieClip;
      private var monster:MovieClip;
      private var params:Object;

      public function MonsterPreview()
      {
         super();
         stage.align = StageAlign.TOP_LEFT;
         stage.scaleMode = StageScaleMode.NO_SCALE;
         holder = MovieClip(addChild(new MovieClip()));
         holder.visible = false;
         params = stage.loaderInfo.parameters;

         if (ExternalInterface.available)
         {
            ExternalInterface.addCallback("playAnim", playAnim);
         }

         var loader:Loader = new Loader();
         loader.contentLoaderInfo.addEventListener(Event.COMPLETE, onComplete, false, 0, true);
         loader.contentLoaderInfo.addEventListener(IOErrorEvent.IO_ERROR, onError, false, 0, true);
         loader.contentLoaderInfo.addEventListener(SecurityErrorEvent.SECURITY_ERROR, onError, false, 0, true);
         loader.load(new URLRequest(String(params.sBase || "") + (params.sFile || "")), new LoaderContext(false, domain));
      }

      public function playAnim(anim:String) : void
      {
         if (monster != null && hasLabel(anim))
         {
            monster.gotoAndPlay(anim);
         }
      }

      private function onComplete(e:Event) : void
      {
         var mc:MovieClip = null;

         try
         {
            var asset:Class = domain.getDefinition(String(params.sLink || "")) as Class;
            mc = new asset();
         }
         catch (err:Error)
         {
            mc = e.target.content as MovieClip;
         }

         if (mc == null)
         {
            notify(String(params.sFailed || "onMonsterPreviewFailed"), "");
            return;
         }

         if (mc.scaleX == 0)
         {
            mc.scaleX = 1;
         }

         monster = mc;
         holder.addChild(mc);
         playAnim(String(params.sAnim || "Idle"));
         fitAndCenter();
         stage.addEventListener(Event.RESIZE, onResize, false, 0, true);

         var names:Array = [];
         for each (var label:* in mc.currentLabels)
         {
            names.push(label.name);
         }

         notify(String(params.sLoaded || "onMonsterPreviewLoaded"), names.join(","));
      }

      private function hasLabel(anim:String) : Boolean
      {
         for each (var label:* in monster.currentLabels)
         {
            if (label.name == anim)
            {
               return true;
            }
         }

         return false;
      }

      private function fitAndCenter() : void
      {
         var sw:Number = stage.stageWidth;
         var sh:Number = stage.stageHeight;

         monster.scaleX = monster.scaleY = 1;
         monster.x = monster.y = 0;

         var bounds:Rectangle = visibleBounds();
         var scale:Number = Math.min(sw * FIT_RATIO / bounds.width, sh * FIT_RATIO / bounds.height);
         monster.scaleX = monster.scaleY = scale;
         monster.x = (sw / 2) - (bounds.x + bounds.width / 2) * scale;
         monster.y = (sh / 2) - (bounds.y + bounds.height / 2) * scale;
         holder.visible = true;
      }

      private function visibleBounds() : Rectangle
      {
         var area:Rectangle = new Rectangle();
         for (var i:int = 0; i < monster.numChildren; i++)
         {
            var child:DisplayObject = monster.getChildAt(i);
            if (child.visible)
            {
               area = area.isEmpty() ? child.getBounds(monster) : area.union(child.getBounds(monster));
            }
         }

         return area.isEmpty() ? monster.getBounds(monster) : area;
      }

      private function onResize(e:Event) : void
      {
         if (monster != null)
         {
            fitAndCenter();
         }
      }

      private function onError(e:Event) : void
      {
         notify(String(params.sFailed || "onMonsterPreviewFailed"), "");
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
